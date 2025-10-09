<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::guard('admin')->attempt($cred, $remember)) {
            return back()
                ->withErrors(['email' => 'メールアドレスまたはパスワードが違います。'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // 名前付きルートに依存せず、確実に /admin/dashboard へ
        return redirect()->intended('/admin/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
