<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail;

// Filament
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

// Relations
use App\Models\Profile;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if (! $user->profile()->exists()) {
                $user->profile()->create([]);
            }
        });
    }

    // ===== Relations =====
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(\App\Models\Job::class, 'favorites')->withTimestamps();
    }

    public function companyProfiles()
    {
        return $this->belongsToMany(\App\Models\CompanyProfile::class, 'company_user')
            ->withTimestamps();
    }

    public function primaryCompanyProfile()
    {
        return $this->hasOne(\App\Models\CompanyProfile::class, 'user_id');
    }

    public function companies()
    {
        return $this->belongsToMany(\App\Models\Company::class, 'company_user', 'user_id', 'company_id')
            ->withTimestamps();
    }

    // ===== Roles / Scopes =====
    public function isAdmin(): bool   { return ($this->role ?? null) === 'admin'; }
    public function isCompany(): bool { return ($this->role ?? null) === 'company'; }
    public function isEnduser(): bool { return ($this->role ?? null) === 'enduser' || ($this->role ?? null) === null; }

    public function scopeAdmins($q)    { return $q->where('role', 'admin'); }
    public function scopeCompanies($q) { return $q->where('role', 'company'); }
    public function scopeEndusers($q)
    {
        return $q->where(function($qq){
            $qq->whereNull('role')->orWhere('role','enduser');
        });
    }

    // ===== Filament Panel Gate =====
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->isAdmin();
    }

    // ===== Notifications =====
    /** メール認証通知はエンドユーザーにのみ送る */
    public function sendEmailVerificationNotification(): void
    {
        if ($this->isEnduser()) {
            $this->notify(new VerifyEmail);
        }
    }

    /** パスワードリセット通知（必要なら日本語版に差し替え可） */
    public function sendPasswordResetNotification($token): void
    {
        // $this->notify(new \App\Notifications\ResetPasswordJa($token));
        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }
}
