<?php

use Illuminate\Support\Facades\Route;

// API ルート用（必要ならここに追記）
Route::middleware('api')->get('/health', function () {
    return response()->json(['ok' => true]);
});
