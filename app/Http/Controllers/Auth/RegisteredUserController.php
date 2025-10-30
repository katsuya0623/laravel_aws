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

    /** 登録処理（エンドユーザー想定／company_name は任意に変更） */
    public function store(Request $request): RedirectResponse
    {
        // 軽い正規化
        $request->merge([
            'name'  => trim((string) $request->name),
            'email' => strtolower(trim((string) $request->email)),
            // 'company_name' はフォームに無い想定なので触らない
        ]);

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:20',
                    // 日本語・英数・スペース・長音符のみ許可（必要なければ外してOK）
                    'regex:/^[\p{L}\p{N}\p{Zs}ー・－]+$/u',
                ],
                // ← ここ！ もともと required だった 'company_name' を削除 or 任意に
                // 'company_name' => ['nullable','string','max:30'],

                'email' => [
                    'required', 'string', 'lowercase', 'email', 'max:255',
                    Rule::unique(User::class, 'email'),
                ],
                'password' => [
                    'required',
                    'confirmed',
                    // 文言に合わせて：数字・記号は任意（必要なら ->numbers(), ->symbols() を足す）
                    Password::min(8)->letters()->mixedCase(),
                    // もし「漏洩パスワードの拒否」をしたいなら下を復活
                    // ->uncompromised(),
                ],
            ],
            [
                'name.regex' => '氏名に記号や絵文字は使用できません。',
                'password.confirmed' => '確認用パスワードが一致しません。',
            ]
        );

        // ★ role 固定：エンドユーザー
        $user = User::create([
            'name'     => (string) $validated['name'],
            'email'    => (string) $validated['email'],
            'password' => Hash::make((string) $validated['password']),
            'role'     => 'enduser',
            // 'company_name' はフォームに無いのでセットしない
        ]);

        // メール認証フロー
        event(new Registered($user));  // VerifyEmail通知
        Auth::login($user);            // 先にログイン

        // 未認証なら必ず認証画面へ
        return redirect()->route('verification.notice');
    }
}
