<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Job extends Model
{
    use HasFactory;

    protected $table = 'recruit_jobs';

    // 既存どおり一括代入OK
    protected $guarded = [];

    // よく使う型をキャスト（あれば勝手に効く）
    protected $casts = [
        'publish_at'   => 'datetime',
        'is_published' => 'boolean',
    ];

    // JSON等に含めたい便利アクセサ
    protected $appends = [
        'image_url',   // 求人にセットした画像の公開URL
        'thumb_url',   // 一覧サムネ（最終決定URL）
    ];

    /** 会社（jobs.company_id → companies.id） */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    /** 公開スコープ（カラムがある場合のみ適用） */
    public function scopePublished($query)
    {
        if (Schema::hasColumn($this->getTable(), 'is_published')) {
            $query->where('is_published', 1);
        }
        return $query;
    }

    /**
     * お気に入り登録したユーザー（多対多）
     * pivot: favorites(user_id, job_id, timestamps)
     */
    public function favoredBy()
    {
        return $this->belongsToMany(\App\Models\User::class, 'favorites')->withTimestamps();
    }

    /**
     * 記事にセットした画像の公開URL
     * - image_path が http(s) の場合はそのまま返す
     * - それ以外は public ディスク（/storage/...）として解決
     */
    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image_path ?? null;
        if (!$path) return null;

        // フルURLならそのまま
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        // storage:link 前提（storage/app/public -> public/storage）
        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * 一覧サムネの最終決定URL
     * 1) 求人の画像（最優先）
     * 2) 会社ロゴ（Company::logo_url）
     * 3) プレースホルダー（/public/images/noimage.svg）
     */
    public function getThumbUrlAttribute(): string
    {
        // 1) 求人にセットされた画像（最優先）
        if ($this->image_url) {
            return $this->image_url;
        }

        // 2) 会社ロゴ（一覧では with('company') 推奨：N+1回避）
        $logoUrl = $this->company?->logo_url;
        if ($logoUrl) {
            return $logoUrl;
        }

        // 3) 最後の保険
        return asset('images/noimage.svg');
    }
}
