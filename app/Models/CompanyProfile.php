<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Validator; // ★ 追加

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        // 互換：代表担当者（単一）
        'user_id',

        'company_name','company_name_kana','description','logo_path',
        'website_url','email','tel',
        'postal_code','prefecture','city','address1','address2',
        'industry','employees','founded_on',

        // 追加
        'is_completed','completed_at',
    ];

    protected $casts = [
        'employees'    => 'integer',
        'founded_on'   => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /** 代表担当者（単一・互換列 user_id） */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /** 担当者（複数） pivot: company_user(company_profile_id, user_id) */
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'company_user')
                    ->withTimestamps();
    }

    /** 代表担当者を設定（互換列へミラー） */
    public function setPrimaryUser(?\App\Models\User $user): void
    {
        $this->user()->associate($user);
        $this->save();
    }

    /** 代表担当者の取得（null許容の糖衣） */
    public function primaryUser(): ?\App\Models\User
    {
        return $this->user; // $company->primaryUser() でも取れるように
    }

    /* ============================================================
       ↓ ここから：フロント表示制御用の完了判定（厳格版）
       ============================================================ */

    /**
     * 必須の充足 + 形式バリデーション（全部OKなら true）
     * ここを満たすまで is_completed は立たない
     */
    public function passesCompletionValidation(): bool
    {
        // まず「必須が埋まっているか」を軽くチェック
        $required = [
            'postal_code','prefecture','city','address1',
            'industry','employees','email',
        ];
        foreach ($required as $key) {
            if (!filled($this->{$key})) return false;
        }

        // 形式チェック（Laravel Validator）
        $v = Validator::make($this->getAttributes(), [
            'email'       => ['required','email','max:255'],
            'website_url' => ['nullable','url','max:255'],
            'tel'         => ['nullable','regex:/^\+?[0-9\-\s()]{7,20}$/'],
            'postal_code' => ['required','string','max:20'],
            'employees'   => ['required','integer','min:1'],
        ]);

        return !$v->fails();
    }

    /** 後方互換：旧メソッド名でも同じ判定を返す */
    public function isCompletedRuntime(): bool
    {
        return $this->passesCompletionValidation();
    }

    /** 完了フラグを同期（保存時などで呼ぶ） */
    public function syncCompletionFlags(): void
    {
        $done = $this->passesCompletionValidation();
        $now  = now();

        if ($done && !$this->is_completed) {
            $this->is_completed = true;
            $this->completed_at = $now;
            $this->saveQuietly(); // イベント発火せず更新
        } elseif (!$done && $this->is_completed) {
            // 条件を満たさなくなったら完了を剥奪
            $this->is_completed = false;
            $this->completed_at = null;
            $this->saveQuietly();
        }
    }

    /** 完了済みのみ取得 */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /** 保存のたびに完了フラグを再評価 */
    protected static function booted()
    {
        static::saved(function (self $profile) {
            $profile->syncCompletionFlags();
        });
    }
}
