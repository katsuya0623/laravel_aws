<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLogin;

Route::prefix('admin')->as('admin.')->group(function () {

    // 未ログイン管理者のみ
    Route::middleware('guest:admin')->group(function () {
        Route::get('login',  [AdminLogin::class, 'show'])->name('login');
        Route::post('login', [AdminLogin::class, 'login'])->name('login.post');
    });

    // ログアウト
    Route::post('logout', [AdminLogin::class, 'logout'])
        ->middleware('auth:admin')
        ->name('logout');

    // /admin/dashboard → Filament ダッシュボード（片方向）
    Route::middleware('auth:admin')->group(function () {
        Route::get('dashboard', function () {
            return redirect()->route('filament.admin.pages.dashboard');
        })->name('dashboard');
    });
});
