<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
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
}
