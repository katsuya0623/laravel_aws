<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password; // ← 追加
use Illuminate\View\View;
use Illuminate\Validation\Rule;          // ← 追加

class RegisteredUserController extends Controller
{
    /** 表示 */
    public function create(): View
    {
        return view('auth.register');
    }

    /** 登録処理 */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email'), // users.email でユニーク
            ],
            'password' => [
                'required',
                'confirmed',                         // password_confirmation と一致
                // 既定で良ければ defaults() のままでもOK
                Password::min(8)->mixedCase()->numbers(), // 8文字以上＋英大小＋数字
                // ->symbols() や ->uncompromised() を足しても可
            ],
            // 'terms' => ['accepted'], // 利用規約チェックを付けたい場合はコメント解除
        ]);

        $user = User::create([
            'name'     => (string) $request->name,
            'email'    => (string) $request->email,
            'password' => Hash::make((string) $request->password),
            'role'     => 'enduser', // ← ここでエンドユーザーに固定
        ]);

        event(new Registered($user));
        Auth::login($user);

        // 直前の intended があればそこへ、なければトップ等へ
        return redirect()->intended(route('home', absolute: false));
        // 既存の dashboard に飛ばしたいなら下でもOK:
        // return redirect(route('dashboard', absolute: false));
    }
}
