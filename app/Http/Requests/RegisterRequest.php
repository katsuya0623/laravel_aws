<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 誰でも登録できるように true
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/', // 英数字と_のみ
            ],
            'company_name' => [
                'required',
                'string',
                'max:50',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(), // セキュリティチェック
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'ユーザー名を入力してください。',
            'username.regex' => 'ユーザー名は英数字とアンダースコアのみ使用できます。',
            'password.confirmed' => '確認用パスワードが一致しません。',
            'password.min' => 'パスワードは8文字以上にしてください。',
        ];
    }
}
