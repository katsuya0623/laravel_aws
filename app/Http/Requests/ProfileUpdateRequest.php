<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            // ===== Breeze既存（users テーブル） =====
            'name'  => ['required','string','max:255'],
            'email' => [
                'required','string','lowercase','email','max:255',
                Rule::unique('users','email')->ignore($this->user()->id),
            ],

            // ===== 画像（新規） =====
            // <input type="file" name="avatar">（5MBまで）
            'avatar' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],

            // ===== profiles 側：基本情報 =====
            'display_name'    => ['nullable','string','max:255'],
            'bio'             => ['nullable','string','max:4000'],
            'last_name'       => ['nullable','string','max:255'],
            'first_name'      => ['nullable','string','max:255'],
            'last_name_kana'  => ['nullable','string','max:255'],
            'first_name_kana' => ['nullable','string','max:255'],
            // male / female / other / no_answer のいずれか（未回答= no_answer）
            'gender'          => ['nullable','in:no_answer,male,female,other'],
            'birthday'        => ['nullable','date'],
            'phone'           => ['nullable','string','max:50'],

            // 住所
            'postal_code'     => ['nullable','string','max:16'],
            'prefecture'      => ['nullable','string','max:255'],
            'city'            => ['nullable','string','max:255'],
            'address1'        => ['nullable','string','max:255'],
            'address2'        => ['nullable','string','max:255'],
            'nearest_station' => ['nullable','string','max:255'],
            'location'        => ['nullable','string','max:255'],

            // URL / SNS
            'website_url'     => ['nullable','url','max:255'],
            'portfolio_url'   => ['nullable','url','max:255'],
            'x_url'           => ['nullable','string','max:255'],
            'instagram_url'   => ['nullable','string','max:255'],
            'sns_x'           => ['nullable','string','max:255'],
            'sns_instagram'   => ['nullable','string','max:255'],

            // ===== JSON：学歴 =====
            'educations'                       => ['nullable','array'],
            'educations.*.school'              => ['nullable','string','max:255'],
            'educations.*.faculty'             => ['nullable','string','max:255'],
            'educations.*.department'          => ['nullable','string','max:255'],
            'educations.*.status'              => ['nullable','string','max:50'],
            'educations.*.period_from'         => ['nullable','date'],
            'educations.*.period_to'           => ['nullable','date'],

            // ===== JSON：職歴 =====
            'work_histories'                   => ['nullable','array'],
            'work_histories.*.company'         => ['nullable','string','max:255'],
            'work_histories.*.from'            => ['nullable','date'],
            'work_histories.*.to'              => ['nullable','date'],
            'work_histories.*.employment_type' => ['nullable','string','max:50'],
            'work_histories.*.dept'            => ['nullable','string','max:255'],
            'work_histories.*.position'        => ['nullable','string','max:255'],
            'work_histories.*.tasks'           => ['nullable','string','max:4000'],
            'work_histories.*.achievements'    => ['nullable','string','max:4000'],

            // ===== JSON：スキル =====
            'skills'                           => ['nullable','array'],
            'skills.*'                         => ['nullable','string','max:100'],

            // ===== JSON：希望条件（チェックボックス） =====
            'desired.positions'                => ['nullable','array'],
            'desired.positions.*'              => ['nullable','string','max:100'],
            'desired.employment_types'         => ['nullable','array'],
            'desired.employment_types.*'       => ['nullable','string','max:100'],
            'desired.locations'                => ['nullable','array'],
            'desired.locations.*'              => ['nullable','string','max:100'],

            // ===== JSON：希望条件（新規追加） =====
            // 第一/第二希望（職種・勤務地の組み合わせ）
            'desired.first_choice.position'    => ['nullable','string','max:255'],
            'desired.first_choice.location'    => ['nullable','string','max:255'],
            'desired.second_choice.position'   => ['nullable','string','max:255'],
            'desired.second_choice.location'   => ['nullable','string','max:255'],

            // 希望時期（プリセット） + 就業可能日
            'desired.hope_timing'              => ['nullable','in:即日,1ヶ月以内,3ヶ月以内,応相談'],
            'desired.available_from'           => ['nullable','date'],

            // そのほか
            'desired.salary_min'               => ['nullable','integer','min:0'],
            'desired.remarks'                  => ['nullable','string','max:2000'],
        ];
    }
}
