<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLogin;
use App\Http\Controllers\Admin\DashboardController;

Route::prefix('admin')->as('admin.')->group(function () {

    // 未ログイン管理者のみ
    Route::middleware('guest:admin')->group(function () {
        Route::get('login',  [AdminLogin::class, 'show'])->name('login');
        // フォームは action="{{ route('admin.login.post') }}" にすること
        Route::post('login', [AdminLogin::class, 'login'])->name('login.post');
        // （お好みで）ブルートフォース対策:
        // ->middleware('throttle:login')
    });

    // ログアウトは認証済みの admin のみ
    Route::post('logout', [AdminLogin::class, 'logout'])
        ->middleware('auth:admin')
        ->name('logout');

    // ダッシュボード（__invoke コントローラ）
    Route::middleware('auth:admin')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });
});
