<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Authentication Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        // 企業・エンドユーザーは基本 web ガードで運用
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
        // 一般ユーザー（企業・エンドユーザー共通）
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // エンドユーザー専用（必要なら残す）
        'enduser' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // 企業ユーザー専用（webと同じDB参照だが別ガードで管理したい場合）
        'company' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // 管理者
        'admin' => [
            'driver'   => 'session',
            'provider' => 'admins',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    | Supported: "eloquent", "database"
    */
    'providers' => [
        // 共通ユーザー（エンドユーザー／企業ユーザー）
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],

        // 管理者
        'admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Admin::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Settings
    |--------------------------------------------------------------------------
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
        'admins' => [
            'provider' => 'admins',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
