<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * ユーザーが認証されていない場合のリダイレクト先を指定
     */
    protected function redirectTo($request)
    {
        // JSONリクエストはリダイレクト不要
        if ($request->expectsJson()) {
            return null;
        }

        // ✅ /admin または admin.* ルートの場合は必ず管理画面ログインへ
        if (
            $request->is('admin') ||
            $request->is('admin/*') ||
            $request->routeIs('admin.*')
        ) {
            return route('admin.login');
        }

        // ✅ その他の全ては通常ログインへ
        return route('login');
    }
}
