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
        'title',
        'excerpt',
        'body',
        'thumbnail_path',
        'published_at',
        'slug',
        'user_id',
        'category_id',
        // custom
        'seo_title',
        'seo_description',
        'is_featured',
        'reading_time',
    ];

    /** 型キャスト */
    protected $casts = [
        'published_at' => 'datetime',
        'is_featured'  => 'boolean',
        'reading_time' => 'integer',
    ];

    /** JSONに含めたい場合は有効化
     *  protected $appends = ['thumbnail_url', 'has_thumbnail'];
     */

    /* =========================
       Relations
       ========================= */
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

    /* =========================
       Mutators
       ========================= */

    /** "0" や 空文字は NULL に矯正 */
    public function setThumbnailPathAttribute($value): void
    {
        $this->attributes['thumbnail_path'] =
            ($value === '0' || $value === 0 || $value === '') ? null : $value;
    }

    /** boolean 正規化 */
    public function setIsFeaturedAttribute($value): void
    {
        $this->attributes['is_featured'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** 空なら null、数値なら1以上の整数に矯正 */
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

    /**
     * サムネイルの公開URLを統一的に取得
     * - http(s):// or // はそのまま返す
     * - '/storage/...','storage/...' は相対化して public ディスクから URL 生成
     * - DB に 'thumbnail' カラムがある場合にもフォールバック
     * - 実ファイルが無ければデフォルト画像にフォールバック
     */
    public function getThumbnailUrlAttribute(): string
    {
        $path = $this->thumbnail_path ?? $this->thumbnail ?? null;

        $fallback = asset('images/noimage.svg');

        if (!$path) {
            return $fallback;
        }

        // 既にフルURL/CDN
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        // '/storage/...' → '...' 、'storage/...' → '...' に正規化
        if (Str::startsWith($path, '/storage/')) {
            $path = Str::after($path, '/storage/');
        }
        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        // 実在チェックしてから公開URLへ
        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)   // => /storage/xxx
            : $fallback;
    }

    /**
     * 「サムネがあるか」を判定（Blade の no image 表示用）
     */
    public function getHasThumbnailAttribute(): bool
    {
        $path = $this->thumbnail_path ?? $this->thumbnail ?? null;
        if (!$path) return false;

        // フルURL or /storage/... は「ある」とみなす（外部CDNや直リンク対応）
        if (Str::startsWith($path, ['http://','https://','//','/storage/'])) {
            return true;
        }

        // 'storage/...' を相対化して存在チェック
        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        return Storage::disk('public')->exists($path);
    }

    /* =========================
       Scopes
       ========================= */

    /** キーワード AND 検索（title/excerpt/body/slug/seo_title/seo_description） */
    public function scopeKeyword($query, ?string $kw)
    {
        if (!$kw) return $query;

        $tokens = preg_split('/\s+/u', trim($kw));
        foreach ($tokens as $t) {
            $like = '%' . $t . '%';
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

    /** 公開済みの記事のみ（必要なら利用） */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /* =========================
       Model events（カテゴリ/タグの同期）
       ========================= */
    protected static function booted(): void
    {
        // CLI 実行時（migrate 等）はスキップ
        if (app()->runningInConsole()) {
            return;
        }

        // 保存前：category_id を反映（null 可）
        static::saving(function (self $post): void {
            if (function_exists('request') && request()->has('category_id')) {
                $post->category()->associate(request()->input('category_id') ?: null);
            }
        });

        // 保存後：tags を sync
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
