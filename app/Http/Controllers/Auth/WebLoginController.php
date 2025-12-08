<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ReCaptchaService;         // ★ 追加
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class WebLoginController extends Controller
{
    public function create()
    {
        // Breeze の login.blade.php を表示（無ければ差し替え）
        return view('auth.login');
    }

    public function store(Request $request, ReCaptchaService $recaptcha) // ★ サービスを DI
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 入力を正規化（余白・大文字小文字の揺れ対策）
        $email    = mb_strtolower(trim($request->input('email')));
        $pass     = (string) $request->input('password');
        $remember = $request->boolean('remember');

        // ★ reCAPTCHA v3 検証 ------------------------------------
        // login.blade 側で <input type="hidden" name="g-recaptcha-response"> を
        // セットしておく前提
        $token = (string) $request->input('g-recaptcha-response');

        if (! $recaptcha->verify($token, 'login')) {  // action 名 'login' は JS 側と合わせる
            return back()
                ->withErrors([
                    'email' => '自動判定によりログインがブロックされました。もう一度お試しください。',
                ])
                ->withInput(['email' => $email]);
        }
        // -----------------------------------------------------

        // ---------- 一時デバッグログ（原因特定用） ----------
        try {
            Log::info('[LOGIN DEBUG] start', [
                'route' => '/login',
                'guard' => 'web',
                'email' => $email,
            ]);

            $u = \App\Models\User::whereRaw('lower(email) = ?', [$email])->first();

            Log::info('[LOGIN DEBUG] user fetched', [
                'exists'   => (bool) $u,
                'id'       => $u?->id,
                'role'     => $u?->role,
                'verified' => (bool) $u?->email_verified_at,
                'pass_len' => $u ? strlen((string) $u->password) : null,
            ]);

            if ($u) {
                $match = Hash::check($pass, $u->password);
                Log::info('[LOGIN DEBUG] hash check', ['match' => $match]);
            }

            $validated = Auth::guard('web')->validate(['email' => $email, 'password' => $pass]);
            Log::info('[LOGIN DEBUG] guard.validate', ['result' => $validated]);
        } catch (\Throwable $e) {
            Log::error('[LOGIN DEBUG] exception', ['msg' => $e->getMessage()]);
        }
        // -----------------------------------------------------

        // ★ web ガードで強制認証（ここがポイント）
        if (! Auth::guard('web')->attempt(['email' => $email, 'password' => $pass], $remember)) {
            // 失敗時、入力メールは保持
            return back()
                ->withErrors(['email' => __('auth.failed')])
                ->withInput(['email' => $email]);
        }

        $request->session()->regenerate();

        // ログイン後はロール別ダッシュボードへ（/dashboard 側で分岐でもOK）
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
