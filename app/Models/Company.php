<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    // ✅ 表示用アクセサを追加
    protected $appends = [
        'logo_url',
        'display_company_name',
        'display_description',
    ];

    // （任意）N+1回避したい場合はコメントアウト解除
    // protected $with = ['profile'];

    /* =============================
       保存直前バリデーション
       ============================= */
    protected static function booted(): void
    {
        static::saving(function (Company $company) {
            $name = (string)($company->name ?? '');

            if (function_exists('normalizer_normalize')) {
                $name = normalizer_normalize($name, \Normalizer::FORM_C);
            }

            if (mb_strlen($name) > 30) {
                throw ValidationException::withMessages([
                    'name' => '企業名は30文字以内で入力してください。',
                ]);
            }
        });
    }

    /* =============================
       リレーション
       ============================= */

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class)->withTimestamps();
    }

    /** company_id ベースの hasOne 関係に統一 */
    public function profile()
    {
        // 第2引数: 子テーブルの外部キー, 第3引数: 親のローカルキー
        return $this->hasOne(\App\Models\CompanyProfile::class, 'company_id', 'id');
    }

    // 招待（既存）
    public function invitations()
    {
        if (Schema::hasTable('company_invitations')) {
            if (Schema::hasColumn('company_invitations', 'company_id')) {
                return $this->hasMany(\App\Models\CompanyInvitation::class, 'company_id');
            }
            if (Schema::hasColumn('company_invitations', 'company_name')) {
                return $this->hasMany(
                    \App\Models\CompanyInvitation::class,
                    'company_name', // 子側キー
                    'name'          // 親側キー
                );
            }
        }
        return $this->hasMany(\App\Models\CompanyInvitation::class, 'company_id');
    }

    // 一覧に has_pending_invite（bool）を載せる（既存）
    public function scopeWithInviteState(Builder $query): Builder
    {
        return $query->withExists([
            'invitations as has_pending_invite' => fn ($q) => $q->where('status', 'pending'),
        ]);
    }

    /* =============================
       アクセサ
       ============================= */

    /**
     * ✅ 企業名（表示用）
     * - profile.company_name があればそれを優先
     * - 無ければ companies.name
     */
    public function getDisplayCompanyNameAttribute(): string
    {
        $profileName = $this->profile?->company_name;
        if (filled($profileName)) {
            return (string) $profileName;
        }

        return (string) ($this->name ?? '');
    }

    /**
     * ✅ 事業内容 / 紹介（表示用）
     * - profile.description があればそれを優先
     * - 無ければ companies.description
     */
    public function getDisplayDescriptionAttribute(): string
    {
        $profileDesc = $this->profile?->description;
        if (filled($profileDesc)) {
            return (string) $profileDesc;
        }

        return (string) ($this->description ?? '');
    }

    /**
     * ロゴURLを堅牢に解決
     */
    public function getLogoUrlAttribute(): ?string
    {
        // 候補（companies.* → profiles.*）
        $candidates = [];
        foreach ([
            $this->getRawLogoPathFromCompanies(),
            optional($this->profile)->logo_path ?? null,
        ] as $p) {
            if ($p) $candidates[] = trim($p);
        }

        foreach ($candidates as $path) {
            // 既にURL
            if (preg_match('#^https?://#i', $path)) return $path;

            // 前処理：先頭スラッシュ排除
            $p = ltrim($path, '/');

            // public/xxx → xxx
            if (Str::startsWith($p, 'public/')) {
                $p = Str::after($p, 'public/');
            }

            // storage/xxx（/storage を直接返す）
            if (Str::startsWith($p, 'storage/')) {
                return asset($p);
            }

            // storage/app/public ?
            if (Storage::disk('public')->exists($p)) {
                return Storage::disk('public')->url($p); // /storage/xxx
            }

            // public 直下 ?
            if (file_exists(public_path($p))) {
                return asset($p);
            }
        }

        // 見つからなければ NoImage
        return asset('images/no-image.svg');
    }

    private function getRawLogoPathFromCompanies(): ?string
    {
        return $this->logo_path
            ?? $this->logo
            ?? $this->thumbnail_path
            ?? null;
    }

    /* ================================
       完了プロフィール企業のみ取得
       ================================ */
    public function scopeWithCompletedProfile($query)
    {
        if (!Schema::hasTable('company_profiles')) return $query;

        $hasCompanyId = Schema::hasColumn('company_profiles', 'company_id');
        $hasCompleted = Schema::hasColumn('company_profiles', 'is_completed');

        if (!$hasCompleted) return $query;

        if ($hasCompanyId) {
            // ✅ 推奨: company_id で結合（より確実）
            return $query
                ->join('company_profiles as cp', 'cp.company_id', '=', 'companies.id')
                ->where('cp.is_completed', true)
                ->select('companies.*');
        }

        // フォールバック: 名前で結合
        $cpNameCol = Schema::hasColumn('company_profiles', 'company_name')
            ? 'company_name'
            : (Schema::hasColumn('company_profiles', 'name') ? 'name' : null);

        if (!$cpNameCol) return $query;

        return $query
            ->join('company_profiles as cp', "cp.$cpNameCol", '=', 'companies.name')
            ->where('cp.is_completed', true)
            ->select('companies.*');
    }
}
