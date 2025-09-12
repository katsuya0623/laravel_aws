<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (int)$this->route('id') === (int)auth()->id();
    }

    public function rules(): array
    {
        return [
            'display_name'   => ['nullable','string','max:80'],
            'bio'            => ['nullable','string','max:2000'],
            'avatar'         => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'website_url'    => ['nullable','url'],
            'x_url'          => ['nullable','url'],
            'instagram_url'  => ['nullable','url'],
            'location'       => ['nullable','string','max:120'],
            'birthday'       => ['nullable','date'],
        ];
    }
}
