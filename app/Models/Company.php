<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    // ★ 明示しておく：companies テーブルを使う
    protected $table = 'companies';

    // 読み取りだけなら fillable は不要だが、今後の更新に備えて一応指定
    protected $fillable = [
        'name',
        'slug',
        'description',
        // 必要なら他のカラムも追加（例：'thumbnail_path', 'website', ...）
    ];

    // もし主キーやタイムスタンプがデフォルトと違う場合はここで調整
    // protected $primaryKey = 'id';
    // public $timestamps = true;
}
