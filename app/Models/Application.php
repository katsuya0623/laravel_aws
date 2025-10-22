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
        'user_id',   // ログインユーザー紐付け（任意）
        'name',
        'kana',              // ← 追加：フリガナ
        'email',
        'phone',
        'current_status',    // ← 追加：現在の状況
        'employment_type',   // ← 追加：希望雇用形態
        'motivation',        // ← 追加：志望動機
        'pr',                // ← 追加：自己PR
        'resume_path',       // ← 追加：添付ファイル
        'ip',                // ← 追加：送信元IP
        'message',           // 既存（任意メッセージ）
        'status',            // 既存（ステータス）
    ];

    /**
     * 求人（多:1）
     */
    public function job()
    {
        return $this->belongsTo(\App\Models\Job::class);
    }

    /**
     * 応募者（多:1）
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
