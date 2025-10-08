<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// ★ 追加：Filament の契約
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class Admin extends Authenticatable implements FilamentUser
{
    use Notifiable;

    // このモデルは admin ガードでログインさせる
    protected $guard = 'admin';

    // まとめて代入可能
    protected $fillable = [
        'name', 'email', 'password',
    ];

    // JSON等に出さない
    protected $hidden = [
        'password', 'remember_token',
    ];

    // Laravel 10+ の推奨：代入時に自動でハッシュ
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime', // カラムを作ったら有効化
            'password' => 'hashed',
        ];
    }

    /**
     * Filament パネルへの入場可否
     * ここで true を返せば管理者はパネルに入れます。
     * （必要ならロール等の条件に置き換えてください）
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * （任意）Filament 上で表示する名前
     */
    public function getFilamentName(): ?string
    {
        return $this->name ?? $this->email;
    }
}
