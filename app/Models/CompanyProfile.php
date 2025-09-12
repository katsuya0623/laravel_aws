<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name','company_name_kana','description','logo_path',
        'website_url','email','tel',
        'postal_code','prefecture','city','address1','address2',
        'industry','employees','founded_on',
    ];

    protected $casts = [
        'employees' => 'integer',
        'founded_on' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
