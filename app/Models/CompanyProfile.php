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

        // ※ 下記2つは存在しない環境があるため fillable には含めない
        // 'is_completed',
        // 'completed_at',
    ];

    protected $casts = [
        'employees'  => 'integer',
        'founded_on' => 'date',
        // 'is_completed' と 'completed_at' はカラムが無い環境があるので casts しない
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
       完了判定（フロント表示制御用）
       ============================================================ */
    public function passesCompletionValidation(): bool
    {
        // 必須が埋まっているか
        $required = [
            'postal_code', 'prefecture', 'city', 'address1',
            'industry', 'employees', 'email',
        ];
        foreach ($required as $key) {
            if (!filled($this->{$key})) return false;
        }

        // 形式チェック
        $v = Validator::make($this->getAttributes(), [
            'email'       => ['required', 'email', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'tel'         => ['nullable', 'regex:/^\+?[0-9\-\s()]{7,20}$/'],
            'postal_code' => ['required', 'string', 'max:20'],
            'employees'   => ['required', 'integer', 'min:1'],
        ]);

        return !$v->fails();
    }

    /** 旧名互換 */
    public function isCompletedRuntime(): bool
    {
        return $this->passesCompletionValidation();
    }

    /** 完了フラグを同期（存在するカラムにのみ反映） */
    public function syncCompletionFlags(): void
    {
        $done = $this->passesCompletionValidation();

        // is_completed / completed_at の列が存在する場合のみ更新
        $hasCompleted = self::hasColumn('is_completed');
        $hasCompletedAt = self::hasColumn('completed_at');

        if (!$hasCompleted && !$hasCompletedAt) {
            // どちらのカラムも無ければ何もしない
            return;
        }

        // 現状の値（存在しない場合は null 扱い）
        $currentCompleted = (bool) ($hasCompleted ? $this->getAttribute('is_completed') : false);

        if ($done && (!$currentCompleted)) {
            if ($hasCompleted)    $this->setAttribute('is_completed', true);
            if ($hasCompletedAt && empty($this->completed_at)) $this->setAttribute('completed_at', now());
            // ここでイベントを再発火させない
            $this->saveQuietly();
        } elseif (!$done && $currentCompleted) {
            if ($hasCompleted)    $this->setAttribute('is_completed', false);
            if ($hasCompletedAt)  $this->setAttribute('completed_at', null);
            $this->saveQuietly();
        }
    }

    /** 完了済みのみ（カラムが無い環境ではフィルタしない） */
    public function scopeCompleted($query)
    {
        return self::hasColumn('is_completed')
            ? $query->where('is_completed', true)
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
