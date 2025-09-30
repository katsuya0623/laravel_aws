<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * After login redirect. （必要なら使ってください）
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // ここで全ルート定義をまとめて読み込む
        $this->routes(function () {

            // ===== API（そのまま）=====
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // ===== Web（既存の /login など）=====
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // ===== Admin（新設: /admin/login, /admin/dashboard など）=====
            if (file_exists(base_path('routes/admin.php'))) {
                Route::middleware('web') // セッション使うので web ミドルウェア
                    ->group(base_path('routes/admin.php'));
            }

            // （将来追加するなら例）
            // if (file_exists(base_path('routes/users.php'))) {
            //     Route::middleware('web')->group(base_path('routes/users.php'));
            // }
            // if (file_exists(base_path('routes/enduser.php'))) {
            //     Route::middleware('web')->group(base_path('routes/enduser.php'));
            // }
            // if (file_exists(base_path('routes/auth.php'))) {
            //     Route::middleware('web')->group(base_path('routes/auth.php'));
            // }
        });
    }

    /**
     * Global rate limit settings. （必要に応じて調整）
     */
    protected function configureRateLimiting(): void
    {
        // API 全体のデフォルト
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // ログイン系（任意で使用。routes側で 'throttle:login' を付与）
        RateLimiter::for('login', function (Request $request) {
            // IP＋メールで制限（5分間に10回など）
            $email = (string) $request->input('email');
            return [
                Limit::perMinutes(5, 10)->by($request->ip()),
                Limit::perMinutes(5, 10)->by($email.$request->ip()),
            ];
        });
    }
}
