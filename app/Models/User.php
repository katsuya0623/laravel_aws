<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail; // ★追加：メール確認を必須化
// use Laravel\Sanctum\HasApiTokens; // APIトークンが必要なら有効化

// ★ Filament 用
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

// ★ 追加：プロフィール自動作成で使用
use App\Models\Profile;

// （任意）日本語文面にしたい場合は独自通知を用意して下のメソッドで使う
// use App\Notifications\VerifyEmailJa;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail // ★追加：MustVerifyEmailを実装
{
    use HasFactory, Notifiable;
    // use HasApiTokens;

    /**
     * まとめて代入可能な属性
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',   // ← 管理人が割り振り時に使う想定
    ];

    /**
     * 配列に隠す属性
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * キャスト
     */
    protected $casts = [
        'email_verified_at' => 'datetime', // ← これがあればOK
        'password'          => 'hashed',   // ← 保存時に自動でハッシュ
        // 'is_active' => 'boolean',
    ];

    /**
     * ★ ユーザー作成時に必ず空のプロフィールを作成
     *   冪等にしてあるので再実行しても安全
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            if (! $user->profile()->exists()) {
                $user->profile()->create([]); // 空でOK（編集画面で埋める）
            }
        });
    }

    /* ========================
       リレーション
       ======================== */

    /** プロフィール（1:1） */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /** お気に入り求人（多対多） pivot: favorites(user_id, job_id) */
    public function favorites()
    {
        return $this->belongsToMany(\App\Models\Job::class, 'favorites')->withTimestamps();
    }

    /** 会社（多対多）：pivot company_user(company_profile_id, user_id) */
    public function companyProfiles()
    {
        return $this->belongsToMany(\App\Models\CompanyProfile::class, 'company_user')
                    ->withTimestamps();
    }

    /** 代表担当として単一紐づけ（互換用）：company_profiles.user_id */
    public function primaryCompanyProfile()
    {
        return $this->hasOne(\App\Models\CompanyProfile::class, 'user_id');
    }

    /* ========================
       便利メソッド / スコープ
       ======================== */

    public function isAdmin(): bool   { return ($this->role ?? null) === 'admin'; }
    public function isCompany(): bool { return ($this->role ?? null) === 'company'; }
    public function isEnduser(): bool { return ($this->role ?? null) === 'enduser' || ($this->role ?? null) === null; }

    public function scopeAdmins($q)   { return $q->where('role', 'admin'); }
    public function scopeCompanies($q){ return $q->where('role', 'company'); }
    public function scopeEndusers($q) { return $q->where(function($qq){
        $qq->whereNull('role')->orWhere('role','enduser');
    }); }

    /* ========================
       Filament パネル入場制御
       ======================== */
    public function canAccessPanel(Panel $panel): bool
    {
        // /admin パネルには admin のみ入場許可
        return $panel->getId() === 'admin' && $this->isAdmin();
    }

    /* ========================
       メール確認 通知（任意で日本語化）
       ======================== */
    // デフォルト文面でOKならこのメソッド自体なくても動きます。
    // 日本語件名・本文にしたい場合のみコメントアウト解除して、
    // 上の use App\Notifications\VerifyEmailJa; も有効化してください。
    /*
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailJa);
    }
    */
    // 会社（多対多）：pivot company_user(company_id, user_id) 用
public function companies()
{
    // 第3, 第4引数でキー名を明示（user_id, company_id）
    return $this->belongsToMany(\App\Models\Company::class, 'company_user', 'user_id', 'company_id')
                ->withTimestamps();
}

}
