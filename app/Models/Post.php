<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // URL 生成に使用
use Illuminate\Support\Str;             // 文字列判定に使用

class Post extends Model
{
    use HasFactory;

    // 一括代入で受け付けるカラム
    protected $fillable = [
        'title',
        'excerpt',           // 追加: 紹介文
        'body',
        'thumbnail_path',
        'published_at',
        'slug',
        'user_id',
        'category_id',
        // 追加カスタム項目
        'seo_title',
        'seo_description',
        'is_featured',
        'reading_time',
    ];

    // 型キャスト
    protected $casts = [
        'published_at' => 'datetime',
        'is_featured'  => 'boolean',
        'reading_time' => 'integer',
    ];

    /* -------------------------
       Relations
    --------------------------*/
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
        // pivot(post_tag) にタイムスタンプ列が無い想定
        return $this->belongsToMany(\App\Models\Tag::class);
    }

    /* -------------------------
       Accessors / Mutators
    --------------------------*/

    /** "0" や 空文字が入ってきたら NULL に矯正する */
    public function setThumbnailPathAttribute($value): void
    {
        $this->attributes['thumbnail_path'] =
            ($value === '0' || $value === 0 || $value === '') ? null : $value;
    }

    /** チェックボックスなどからの値をきれいにboolean化 */
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
            $this->attributes['reading_time'] = max(1, (int)$value);
        }
    }

    /**
     * ★ サムネイルの公開URLを統一的に取得
     * - 既に http(s):// または // ならそのまま返す
     * - '/storage/...' や 'storage/...' で保存されていても正規化して公開URLへ
     * - DB が 'thumbnail' カラムの場合にもフォールバック
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        $path = $this->thumbnail_path ?? $this->thumbnail ?? null;
        if (!$path) {
            return null; // プレースホルダを使いたい場合はビュー側で ?? asset('images/noimage.jpg')
        }

        // すでにフルURL/CDN
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        // 先頭 '/storage/' を相対パスに正規化
        if (Str::startsWith($path, '/storage/')) {
            $path = Str::after($path, '/storage/');
        }
        // 先頭 'storage/' を相対パスに正規化
        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        // public ディスクから公開URLへ（/storage/xxxx 形式）
        return Storage::disk('public')->url($path);
    }

    /* -------------------------
       Scopes
    --------------------------*/

    /** キーワードAND検索（title/excerpt/body/slug/seo_title/seo_description） */
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

    /* -------------------------
       Model events（カテゴリ/タグの同期）
       ※ コントローラ側でsyncしている場合は二重でも問題はありません
    --------------------------*/
    protected static function booted(): void
    {
        // CLI 実行時（migrate等）はスキップ
        if (app()->runningInConsole()) {
            return;
        }

        // 保存前：category_id を反映（null可）
        static::saving(function (self $post): void {
            if (function_exists('request') && request()->has('category_id')) {
                $post->category()->associate(request()->input('category_id') ?: null);
            }
        });

        // 保存後：tags を sync
        static::saved(function (self $post): void {
            if (method_exists($post, 'tags') && function_exists('request') && request()->has('tags')) {
                $tagIds = array_filter((array) request()->input('tags', []));
                $post->tags()->sync($tagIds);
            }
        });
    }
}
