<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $table = 'company_profiles';

    protected $fillable = [
        // 紐づく会社/ユーザー
        'company_id',
        'user_id',

        // 基本情報
        'company_name',
        'company_name_kana',
        'description',
        'logo_path',
        'website_url',
        'email',
        'tel',

        // 住所
        'postal_code',
        'prefecture',
        'city',
        'address1',
        'address2',

        // 任意メタ
        'industry',
        'employees',
        'founded_on',
        // ※ is_completed / completed_at は存在しない環境があるので fillable には含めない
    ];

    protected $casts = [
        'employees'  => 'integer',
        'founded_on' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // is_completed は列がある環境で整数として扱いたい
        // （列が無い環境では getAttribute が null を返すだけ）
        'is_completed' => 'integer',
    ];

    /* ------------------------------
     | ユーティリティ
     |------------------------------*/
    public static function hasColumn(string $col): bool
    {
        return Schema::hasColumn((new static)->getTable(), $col);
    }

    /* ------------------------------
     | リレーション
     |------------------------------*/
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'company_user')->withTimestamps();
    }

    public function setPrimaryUser(?\App\Models\User $user): void
    {
        $this->user()->associate($user);
        $this->save();
    }

    public function primaryUser(): ?\App\Models\User
    {
        return $this->user;
    }

    /* ============================================================
       完了判定（フォームの赤＊に合わせて揃える）
       - 必須: description, postal_code, prefecture, city, address1, industry
       - 会社名カナ: company_name_kana または kana のどちらか
       - 従業員数: employees または employees_count のどちらか
       - email/website/tel は任意（必須から外す）
       ============================================================ */
    public function passesCompletionValidation(): bool
    {
        // 1) まず列の存在を確認しつつ値をチェック
        $requiredCols = [
            'description',
            'postal_code',
            'prefecture',
            'city',
            'address1',
            'industry',
        ];

        foreach ($requiredCols as $col) {
            // 列が存在しない場合は “判定対象外”
            if (!self::hasColumn($col)) {
                continue;
            }
            if (!filled($this->getAttribute($col))) {
                return false;
            }
        }

        // 2) 会社名カナ（どれか一つ埋まっていればOK）
        $kanaOk = false;
        foreach (['company_name_kana', 'kana'] as $col) {
            if (self::hasColumn($col) && filled($this->getAttribute($col))) {
                $kanaOk = true; break;
            }
        }
        if (!$kanaOk) return false;

        // 3) 従業員数（どちらかが存在して埋まっていればOK）
        $empOk = false;
        foreach (['employees', 'employees_count'] as $col) {
            if (self::hasColumn($col) && filled($this->getAttribute($col))) {
                $empOk = true; break;
            }
        }
        // 列が両方とも存在しない環境は “判定対象外” として true 扱い
        if (!(self::hasColumn('employees') || self::hasColumn('employees_count'))) {
            $empOk = true;
        }
        if (!$empOk) return false;

        // 4) 軽い形式チェック（任意項目は nullable）
        $rules = [
            'description' => ['nullable', 'string', 'max:2000'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'prefecture'  => ['nullable', 'string', 'max:255'],
            'city'        => ['nullable', 'string', 'max:255'],
            'address1'    => ['nullable', 'string', 'max:255'],
            'industry'    => ['nullable', 'string', 'max:255'],
            'company_name_kana' => ['nullable', 'string', 'max:255'],
            'kana'        => ['nullable', 'string', 'max:255'],
            'employees'   => ['nullable', 'integer', 'min:1'],
            'employees_count' => ['nullable', 'integer', 'min:1'],
            'email'       => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'tel'         => ['nullable', 'regex:/^\+?[0-9\-\s()]{7,20}$/'],
        ];

        // バリデーションは “存在する列だけ” に限定
        $data = [];
        foreach (array_keys($rules) as $col) {
            if (self::hasColumn($col)) {
                $data[$col] = $this->getAttribute($col);
            }
        }

        $v = Validator::make($data, array_intersect_key($rules, $data));
        return !$v->fails();
    }

    /** 旧名互換 */
    public function isCompletedRuntime(): bool
    {
        return $this->passesCompletionValidation();
    }

    /** 完了フラグを同期（存在カラムのみ操作／手動で立てた 1 を尊重） */
    public function syncCompletionFlags(): void
    {
        $hasCompleted   = self::hasColumn('is_completed');
        $hasCompletedAt = self::hasColumn('completed_at');

        if (!$hasCompleted && !$hasCompletedAt) {
            return; // どちらのカラムも無ければ何もしない
        }

        // ① DB の is_completed=1 を優先的に尊重（手動更新を潰さない）
        $current = $hasCompleted ? (int) $this->getAttribute('is_completed') : 0;
        if ($current === 1) {
            // completed_at だけ補完して終了
            if ($hasCompletedAt && empty($this->completed_at)) {
                $this->setAttribute('completed_at', now());
                $this->saveQuietly();
            }
            return;
        }

        // ② ランタイム判定で充足していれば 1 を立てる
        $done = $this->passesCompletionValidation();

        if ($done) {
            if ($hasCompleted)   $this->setAttribute('is_completed', 1);
            if ($hasCompletedAt) $this->setAttribute('completed_at', $this->completed_at ?: now());
            $this->saveQuietly();
        } else {
            // 充足していない場合は “明示的に 0 を書き戻さない” （手動 1 を落とさない設計）
            // 必要があれば以下を解放:
            // if ($hasCompleted)   $this->setAttribute('is_completed', 0);
            // if ($hasCompletedAt) $this->setAttribute('completed_at', null);
            // $this->saveQuietly();
        }
    }

    /** 完了済みのみ（カラムが無い環境ではフィルタしない） */
    public function scopeCompleted($query)
    {
        return self::hasColumn('is_completed')
            ? $query->where('is_completed', 1)
            : $query;
    }

    /** 保存のたびに完了フラグを再評価（存在カラムのみ操作） */
    protected static function booted()
    {
        static::saved(function (self $profile) {
            $profile->syncCompletionFlags();
        });
    }
}
