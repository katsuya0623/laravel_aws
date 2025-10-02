<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        // 互換：代表担当者（単一）
        'user_id',

        'company_name','company_name_kana','description','logo_path',
        'website_url','email','tel',
        'postal_code','prefecture','city','address1','address2',
        'industry','employees','founded_on',
    ];

    protected $casts = [
        'employees'  => 'integer',
        'founded_on' => 'date',
    ];

    /** 代表担当者（単一・互換列 user_id） */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /** 担当者（複数） pivot: company_user(company_profile_id, user_id) */
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'company_user')
                    ->withTimestamps();
    }

    /** 代表担当者を設定（互換列へミラー） */
    public function setPrimaryUser(?\App\Models\User $user): void
    {
        $this->user()->associate($user);
        $this->save();
    }

    /** 代表担当者の取得（null許容の糖衣） */
    public function primaryUser(): ?\App\Models\User
    {
        return $this->user; // $company->primaryUser() でも取れるように
    }
}
