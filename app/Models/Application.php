<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    /**
     * 一括代入で保存を許可するカラム
     */
    protected $fillable = [
        'job_id',
        'user_id',   // 追加：ログインユーザーを紐づけ
        'name',
        'email',
        'phone',
        'message',
        'status',    // 追加：初期値 'applied' などを保存
    ];

    /**
     * 求人（多:1）
     */
    public function job()
    {
        return $this->belongsTo(\App\Models\Job::class);
    }

    /**
     * 応募者（多:1）※ users にレコードがある場合のみ利用
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
