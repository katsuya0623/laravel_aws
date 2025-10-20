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
        'user_id','category_id',
        'seo_title','seo_description','is_featured','reading_time',
        'sponsor_company_id',
    ];

    /** 型キャスト */
    protected $casts = [
        'published_at' => 'datetime',
        'is_featured'  => 'boolean',
        'reading_time' => 'integer',
    ];

    /** ルートモデルバインディングは slug を使用 */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* =========================
       Relations
       ========================= */
    public function author() { return $this->morphTo(); }
    public function user() { return $this->belongsTo(User::class); }
    public function category() { return $this->belongsTo(\App\Models\Category::class); }
    public function tags() { return $this->belongsToMany(\App\Models\Tag::class); }
    public function sponsorCompany() { return $this->belongsTo(\App\Models\Company::class, 'sponsor_company_id'); }

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
        $this->attributes['reading_time'] =
            ($value === '' || $value === null) ? null : max(1, (int) $value);
    }

    /** 空/0/空文字なら NULL にし、FK違反を避ける */
    public function setSponsorCompanyIdAttribute($value): void
    {
        $this->attributes['sponsor_company_id'] =
            ($value === '' || $value === null || $value === 0 || $value === '0')
            ? null
            : (int) $value;
    }

    /* =========================
       Accessors
       ========================= */
    public function getAuthorNameAttribute(): string
    {
        return optional($this->author)->name
            ?? optional($this->user)->name
            ?? '管理者';
    }

    public function getThumbnailUrlAttribute(): string
    {
        $path = $this->thumbnail_path ?? $this->thumbnail ?? null;
        $fallback = asset('images/noimage.svg');
        if (!$path) return $fallback;

        if (Str::startsWith($path, ['http://','https://','//'])) return $path;

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

    public function scopeSponsored($query)
    {
        return $query->whereNotNull('sponsor_company_id');
    }

    /* =========================
       Model events
       ========================= */
    protected static function booted(): void
    {
        static::creating(function (self $post): void {
            // slug を必ず自動生成（日本語対応＆ユニーク）
            if (empty($post->slug)) {
                $post->slug = static::makeUniqueSlug($post->title ?? '');
            }

            // フォームから user_id が来ていても無視して、
            // 必ず "users" に実在するIDで上書きする（adminのみ作成運用）
            $admin = auth('admin')->user();

            // adminと同じメールのusers.idを優先
            $ownerId = $admin
                ? \App\Models\User::where('email', $admin->email)->value('id')
                : null;

            // 見つからなければ users の最小ID（=必ず存在）でフォールバック
            if (!$ownerId) {
                $ownerId = \App\Models\User::query()->min('id');
            }

            if (!$ownerId) {
                // usersが空なら明示的に失敗させる
                throw new \RuntimeException('No users found. Please create at least one user to own posts.');
            }

            $post->user_id = $ownerId; // ★ 強制上書き
        });

        // Console中は request 依存の処理をスキップ
        if (app()->runningInConsole()) return;

        // 保存時：カテゴリ紐付け
        static::saving(function (self $post): void {
            if (function_exists('request') && request()->has('category_id')) {
                $post->category()->associate(request()->input('category_id') ?: null);
            }
        });

        // 保存後：タグ同期
        static::saved(function (self $post): void {
            if (method_exists($post, 'tags')
                && function_exists('request')
                && request()->has('tags')) {
                $tagIds = array_filter((array) request()->input('tags', []));
                $post->tags()->sync($tagIds);
            }
        });
    }

    /* =========================
       Helpers
       ========================= */
    /** タイトルからユニークな slug を生成（日本語対応・重複回避） */
    protected static function makeUniqueSlug(string $base): string
    {
        $base = Str::slug($base, '-');

        if ($base === '') {
            $base = 'post-'.now()->format('Ymd-His').'-'.substr(uniqid('', true), -4);
        }

        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }
        return $slug;
    }
}
