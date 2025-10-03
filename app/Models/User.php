<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens; // APIトークンが必要なら有効化

// ★ Filament 用
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;
    // use HasApiTokens;

    /**
     * まとめて代入可能な属性
     * ※ 管理画面から role を更新する運用なら 'role' を含める
     *   もし厳密に守るなら 'role' は含めず、コントローラで $user->role=... と代入して save()
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
        'email_verified_at' => 'datetime',
        'password'          => 'hashed', // ← 保存時に自動でハッシュ
        // 'is_active' => 'boolean',     // users に列があれば有効化
    ];

    /* ========================
       リレーション
       ======================== */

    /** プロフィール（1:1） */
    public function profile()
    {
        return $this->hasOne(\App\Models\Profile::class);
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

    // 役割スコープ（必要なら）
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
}
