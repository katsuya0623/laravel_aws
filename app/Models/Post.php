<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    // ここに存在する/しないは自由。無いカラムは無視されます
    protected $fillable = [
        'title', 'slug', 'body',
        'thumbnail_path',
        'is_published', 'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * 画像URL（http(s) or storage両対応）
     */
    public function getThumbUrlAttribute(): ?string
    {
        $p = $this->thumbnail_path ?? null;
        if (!$p) return null;

        // すでに外部URLならそのまま
        if (Str::startsWith($p, ['http://', 'https://'])) {
            return $p;
        }

        // 相対パスは /storage/... に解決
        return Storage::url($p);
    }

    /**
     * 任意：公開スコープ（存在すればコントローラが使います）
     */
    public function scopePublished($q)
    {
        return $q
            ->when(
                \Schema::hasColumn($this->getTable(), 'is_published'),
                fn($qq) => $qq->where('is_published', true)
            )
            ->when(
                \Schema::hasColumn($this->getTable(), 'published_at'),
                fn($qq) => $qq->where(function ($w) {
                    $w->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
            );
    }
}
