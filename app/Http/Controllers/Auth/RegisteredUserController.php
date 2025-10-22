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
        // 軽い正規化（前後スペース除去・メールは小文字化）
        $request->merge([
            'name'         => trim((string) $request->name),
            'company_name' => trim((string) $request->company_name),
            'email'        => strtolower(trim((string) $request->email)),
        ]);

        $request->validate(
            [
                // 氏名：日本語/英字/数字/スペース/「ー・－」のみ許可（記号・絵文字NG）
                'name' => [
                    'required',
                    'string',
                    'min:2',
                    'max:20',
                    'regex:/^[\p{L}\p{N}\p{Zs}ー・－]+$/u',
                ],

                // 企業名：必須・30文字まで
                'company_name' => [
                    'required',
                    'string',
                    'max:30',
                ],

                // メール：ユニーク & 小文字化
                'email' => [
                    'required', 'string', 'lowercase', 'email', 'max:255',
                    Rule::unique(User::class, 'email'),
                ],

                // パスワード：8文字以上 + 英大文字/小文字（数字・記号は任意）＋漏洩チェック
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->uncompromised(),
                ],
                // 'terms' => ['accepted'], // 規約同意が必要ならコメント解除
            ],
            [
                'name.regex' => '氏名に記号や絵文字は使用できません。',
                'password.confirmed' => '確認用パスワードが一致しません。',
            ]
        );

        $user = User::create([
            'name'         => (string) $request->name,
            'email'        => (string) $request->email,
            'password'     => Hash::make((string) $request->password),
            'role'         => 'enduser', // エンドユーザー固定
            // ※ users テーブルに company_name カラム（string(30)）が必要
            'company_name' => (string) $request->company_name,
        ]);

        event(new Registered($user));
        Auth::login($user);

        // 直前の intended があればそこへ、なければトップへ
        return redirect()->intended(route('home', absolute: false));
    }
}
