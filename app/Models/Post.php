<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    /** 一括代入 */
    protected $fillable = [
        'title','excerpt','body','thumbnail_path','published_at','slug',
        'user_id','category_id', // ← 後方互換のため残す（そのうち削除でOK）
        'seo_title','seo_description','is_featured','reading_time',
        // ★ author_type/author_id は意図せぬ偽装を防ぐため fillable に入れない
        'sponsor_company_id', // ★ 追加：スポンサー企業のFK
    ];

    /** 型キャスト */
    protected $casts = [
        'published_at' => 'datetime',
        'is_featured'  => 'boolean',
        'reading_time' => 'integer',
    ];

    /* =========================
       Relations
       ========================= */

    /**
     * ★ 投稿者（admin / user いずれも可）
     *   - posts.author_type / posts.author_id を参照
     */
    public function author()
    {
        return $this->morphTo();
    }

    /**
     * 既存の後方互換（将来的に削除予定）
     * 旧データや、まだ user_id を参照している箇所のために残す
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(\App\Models\Tag::class);
    }

    /**
     * ★ 追加：スポンサー企業（任意）
     */
    public function sponsorCompany()
    {
        return $this->belongsTo(\App\Models\Company::class, 'sponsor_company_id');
    }

    /* =========================
       Mutators
       ========================= */

    public function setThumbnailPathAttribute($value): void
    {
        $this->attributes['thumbnail_path'] =
            ($value === '0' || $value === 0 || $value === '') ? null : $value;
    }

    public function setIsFeaturedAttribute($value): void
    {
        $this->attributes['is_featured'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function setReadingTimeAttribute($value): void
    {
        if ($value === '' || $value === null) {
            $this->attributes['reading_time'] = null;
        } else {
            $this->attributes['reading_time'] = max(1, (int) $value);
        }
    }

    /* =========================
       Accessors
       ========================= */

    /** 投稿者名（admin/user どちらでも取れる・未設定は「管理者」） */
    public function getAuthorNameAttribute(): string
    {
        return optional($this->author)->name
            ?? optional($this->user)->name   // 旧データ救済
            ?? '管理者';
    }

    public function getThumbnailUrlAttribute(): string
    {
        $path = $this->thumbnail_path ?? $this->thumbnail ?? null;
        $fallback = asset('images/noimage.svg');
        if (!$path) return $fallback;

        if (Str::startsWith($path, ['http://', 'https://', '//'])) return $path;

        if (Str::startsWith($path, '/storage/')) $path = Str::after($path, '/storage/');
        if (Str::startsWith($path, 'storage/'))  $path = Str::after($path, 'storage/');

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : $fallback;
    }

    public function getHasThumbnailAttribute(): bool
    {
        $path = $this->thumbnail_path ?? $this->thumbnail ?? null;
        if (!$path) return false;
        if (Str::startsWith($path, ['http://','https://','//','/storage/'])) return true;
        if (Str::startsWith($path, 'storage/')) $path = Str::after($path, 'storage/');
        return Storage::disk('public')->exists($path);
    }

    /* =========================
       Scopes
       ========================= */

    public function scopeKeyword($query, ?string $kw)
    {
        if (!$kw) return $query;
        $tokens = preg_split('/\s+/u', trim($kw));
        foreach ($tokens as $t) {
            $like = '%'.$t.'%';
            $query->where(function ($qq) use ($like) {
                $qq->where('title', 'like', $like)
                   ->orWhere('excerpt', 'like', $like)
                   ->orWhere('body', 'like', $like)
                   ->orWhere('slug', 'like', $like)
                   ->orWhere('seo_title', 'like', $like)
                   ->orWhere('seo_description', 'like', $like);
            });
        }
        return $query;
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * ★ 追加：スポンサー付き記事のみ
     */
    public function scopeSponsored($query)
    {
        return $query->whereNotNull('sponsor_company_id');
    }

    /* =========================
       Model events（カテゴリ/タグの同期）
       ========================= */
    protected static function booted(): void
    {
        if (app()->runningInConsole()) return;

        static::saving(function (self $post): void {
            if (function_exists('request') && request()->has('category_id')) {
                $post->category()->associate(request()->input('category_id') ?: null);
            }
        });

        static::saved(function (self $post): void {
            if (method_exists($post, 'tags')
                && function_exists('request')
                && request()->has('tags')) {
                $tagIds = array_filter((array) request()->input('tags', []));
                $post->tags()->sync($tagIds);
            }
        });
    }
}
