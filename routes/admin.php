<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLogin;

Route::prefix('admin')->as('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login',  [AdminLogin::class, 'show'])->name('login');
        Route::post('login', [AdminLogin::class, 'login'])->name('login.post');
    });

    Route::post('logout', [AdminLogin::class, 'logout'])
        ->middleware('auth:admin')
        ->name('logout');
});
