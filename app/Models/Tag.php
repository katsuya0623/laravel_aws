<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    // タグ名やスラッグを保存する場合に備えて
    protected $fillable = ['name', 'slug'];

    // tagsテーブルに created_at / updated_at が無いなら false 推奨
    public $timestamps = false;

    public function posts()
    {
        // post_tag（pivot）に timestamps は使わない
        return $this->belongsToMany(Post::class);
    }
}
