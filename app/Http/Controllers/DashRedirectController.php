<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class DashRedirectController extends Controller
{
    public function index(Request $request)
    {
        // 現在のルート名（= dashboard）を取得して自己ループを防ぐ
        $self = Route::currentRouteName();

        // 1) 管理者ログイン中 → 管理ダッシュボード
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        // 2) 一般ユーザーログイン中 → 役割/既存のユーザーダッシュボードへ
        if (Auth::check()) {
            $u = $request->user();

            // 企業ユーザーなど（必要に応じて調整）
            if (($u->role ?? null) === 'company' && Route::has('users.dashboard')) {
                return redirect()->route('users.dashboard');
            }

            // 候補から“dashboard（自分自身）”は除外する
            foreach (['user.dashboard', 'mypage.dashboard'] as $name) {
                if ($name !== $self && Route::has($name)) {
                    return redirect()->route($name);
                }
            }

            // ユーザー用ダッシュボードが未実装ならTOPへ
            return redirect()->to('/');
        }

        // 3) 未ログイン → ログイン画面（なければTOP）
        return Route::has('login') ? redirect()->route('login') : redirect('/');
    }
}
