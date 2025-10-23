<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyInvitation extends Model
{
    use HasFactory;

    // ★ これを追加（create() で入れるカラムを許可）
    protected $fillable = [
        'email',
        'company_name',
        'company_id',
        'token',
        'expires_at',
        'status',
        'invited_by',
    ];
    // もしくは全部許可したいなら ↓
    // protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // （任意）リレーション
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
