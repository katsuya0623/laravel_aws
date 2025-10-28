<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /** 登録画面表示 */
    public function create(): View
    {
        return view('auth.register');
    }

    /** 登録処理（新規ユーザーのみ検証） */
    public function store(Request $request): RedirectResponse
    {
        // 軽い正規化
        $request->merge([
            'name'         => trim((string) $request->name),
            'company_name' => trim((string) $request->company_name),
            'email'        => strtolower(trim((string) $request->email)),
        ]);

        $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:20',
                    'regex:/^[\p{L}\p{N}\p{Zs}ー・－]+$/u',
                ],
                'company_name' => ['required', 'string', 'max:30'],
                'email' => [
                    'required', 'string', 'lowercase', 'email', 'max:255',
                    Rule::unique(User::class, 'email'),
                ],
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->uncompromised(),
                ],
            ],
            [
                'name.regex' => '氏名に記号や絵文字は使用できません。',
                'password.confirmed' => '確認用パスワードが一致しません。',
            ]
        );

        // ★ role 固定：エンドユーザー
        $user = User::create([
            'name'         => (string) $request->name,
            'email'        => (string) $request->email,
            'password'     => Hash::make((string) $request->password),
            'role'         => 'enduser',
            'company_name' => (string) $request->company_name,
        ]);

        /**
         * ==========================================================
         * ✅ メール認証フロー
         * ==========================================================
         */
        if ($user->isEnduser()) {
            // 1. イベント発火（VerifyEmail 通知送信）
            event(new Registered($user));

            // 2. ログイン状態にする
            Auth::login($user);

            // 3. メール未認証なら確実に verification.notice に飛ばす
            if (is_null($user->email_verified_at)) {
                return redirect()->route('verification.notice');
            }

            // （認証済なら通常通り）
            return redirect()->intended(route('home', absolute: false));
        }

        /**
         * ==========================================================
         * ✅ 企業・管理者登録（後日拡張用）
         * ==========================================================
         */
        $user->forceFill(['email_verified_at' => now()])->save();
        Auth::login($user);
        return redirect()->intended(route('home', absolute: false));
    }
}
