<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin; // ← ここがポイント

class LoginController extends Controller
{
    public function show()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
            // checkbox は boolean にしなくてもOK。使うときに boolean() で拾う
        ]);

        // admins プロバイダを使って認証
        $remember = $request->boolean('remember');

        if (! Auth::guard('admin')->attempt($cred, $remember)) {
            // 情報漏えい防止のためメッセージは固定
            return back()
                ->withErrors(['email' => 'メールアドレスまたはパスワードが違います。'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // Breeze/Jetstream互換: intended を使い、相対URL指定
        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
