<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    | Supported: "session"
    */
    'guards' => [
        // 既定（共通）
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // （必要なら残すエイリアス。使っていなければ削ってOK）
        'enduser' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        'users' => [ // ← これも使っていなければ削ってOK
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // 管理者：★admins プロバイダを見るように修正
        'admin' => [
            'driver'   => 'session',
            'provider' => 'admins',  // ← ココを users → admins に
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    | Supported: "eloquent", "database"
    */
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],

        // ★ 追加：管理者用プロバイダ（adminsテーブル & App\Models\Admin）
        'admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Admin::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
        // ★ 管理者のリセットも分けたい場合（任意）
        'admins' => [
            'provider' => 'admins',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
