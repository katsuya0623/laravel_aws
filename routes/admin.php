<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLogin;
use App\Http\Controllers\Admin\DashboardController;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login',  [AdminLogin::class, 'show'])->name('login');
        Route::post('/login', [AdminLogin::class, 'login'])->name('login.post');
    });

    Route::post('/logout', [AdminLogin::class, 'logout'])->name('logout');

    Route::middleware(['auth:admin'])->group(function () {
        // ★ ここを __invoke 用に変更（メソッド指定なし）
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
    });
});
