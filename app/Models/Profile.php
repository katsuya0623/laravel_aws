<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',

        // 既存
        'display_name',
        'bio',
        'avatar_path',
        'website_url',
        'x_url',
        'instagram_url',
        'location',
        'birthday',

        // 追加（基本情報）
        'last_name',
        'first_name',
        'last_name_kana',
        'first_name_kana',
        'gender',            // male / female / other / no_answer
        'phone',

        // 追加（住所）
        'postal_code',
        'prefecture',
        'city',
        'address1',
        'address2',
        'nearest_station',

        // 追加（URL / SNS）
        'portfolio_url',
        'sns_x',
        'sns_instagram',

        // 追加（JSONブロック）
        'educations',        // array: [{school, faculty, department, period_from, period_to, status}, ...]
        'work_histories',    // array: [{company, from, to, employment_type, dept, position, tasks, achievements}, ...]
        'skills',            // array: ["Photoshop","Figma",...]
        'desired',           // array: {positions:[], employment_types:[], locations:[], salary_min, available_from, remarks}
    ];

    protected $casts = [
        'birthday'       => 'date',
        'educations'     => 'array',
        'work_histories' => 'array',
        'skills'         => 'array',
        'desired'        => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
