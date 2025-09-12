<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\LandingController;
use App\Http\Controllers\Front\ArticleController;
use App\Http\Controllers\Front\CompanyController;
use App\Http\Controllers\Front\JobController;

/*
|---------------------------------------
| Front routes (最低限)
|---------------------------------------
*/
Route::get('/', [LandingController::class, 'index'])->name('front.home');

Route::prefix('articles')->name('front.articles.')->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('index');
    Route::get('/{slug}', [ArticleController::class, 'show'])->name('show');
});

Route::prefix('company')->name('front.company.')->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('index');
    Route::get('/{slug}', [CompanyController::class, 'show'])->name('show');
});

Route::prefix('jobs')->name('front.jobs.')->group(function () {
    Route::get('/', [JobController::class, 'index'])->name('index');
    Route::get('/{slug}', [JobController::class, 'show'])->name('show');
});

/*
|---------------------------------------
| 認証系ルートが別ファイルなら読み込み
|---------------------------------------
*/
if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}
