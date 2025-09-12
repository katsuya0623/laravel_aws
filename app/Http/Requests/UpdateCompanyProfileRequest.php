<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // ユーザー自身の会社情報のみ扱うUI想定
    }

    public function rules(): array
    {
        return [
            'company_name'      => ['required','string','max:120'],
            'company_name_kana' => ['nullable','string','max:120'],
            'description'       => ['nullable','string','max:4000'],
            'logo'              => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'website_url'       => ['nullable','url'],
            'email'             => ['nullable','email','max:255'],
            'tel'               => ['nullable','string','max:50'],
            'postal_code'       => ['nullable','string','max:16'],
            'prefecture'        => ['nullable','string','max:50'],
            'city'              => ['nullable','string','max:100'],
            'address1'          => ['nullable','string','max:120'],
            'address2'          => ['nullable','string','max:120'],
            'industry'          => ['nullable','string','max:100'],
            'employees'         => ['nullable','integer','min:1','max:100000'],
            'founded_on'        => ['nullable','date'],
        ];
    }
}
