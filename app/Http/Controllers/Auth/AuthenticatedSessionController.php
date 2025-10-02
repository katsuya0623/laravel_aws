<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// ★ 追加
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * ポイント：
     * - 認証実行前に「admin を一般ログインから拒否」
     * - メッセージは一般化（ユーザー名/権限の有無を推測させない）
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // ▼ 認証前チェック（セッション未発行で終了）
        if ($user = User::where('email', $request->input('email'))->first()) {
            if (($user->role ?? null) === 'admin') {
                throw ValidationException::withMessages([
                    'email' => 'メールアドレスまたはパスワードが違います。', // 列挙対策で一般化
                ]);
            }
        }

        // ▼ 既存どおり Breeze の認証を実行（webガード）
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
