<?php

use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\PostController as FrontPostController;
use App\Http\Controllers\Front\CategoryController;
use App\Http\Controllers\Front\TagController;

// フロントトップは /blog に移動（route名は変えない）
Route::get('/blog', [HomeController::class, 'index'])->name('front.home');

Route::get('/posts', [FrontPostController::class, 'index'])->name('front.posts.index');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('front.category.show');
Route::get('/tag/{slug}', [TagController::class, 'show'])->name('front.tag.show');
