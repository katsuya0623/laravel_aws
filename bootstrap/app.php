<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ★ ミドルウェアのエイリアス定義（Laravel 12 推奨）
        $middleware->alias([
            'admin'   => \App\Http\Middleware\AdminOnly::class,
            'active'  => \App\Http\Middleware\EnsureUserIsActive::class,

            // 追加分 ↓
            'role'    => \App\Http\Middleware\EnsureUserRole::class,
            'signed'  => \Illuminate\Routing\Middleware\ValidateSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
