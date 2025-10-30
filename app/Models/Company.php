<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;   // 既存
use Illuminate\Support\Facades\Schema;          // 既存
use Illuminate\Database\Eloquent\Builder;       // 既存
use Illuminate\Support\Str;                     // ★ 追加：パス正規化に使用

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected $appends = ['logo_url'];

    // ===== 保存直前バリデーション（既存） =====
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

    /** リレーション（既存） */
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class)->withTimestamps();
    }

    public function profile(): ?\App\Models\CompanyProfile
    {
        if (empty($this->name)) return null;
        return \App\Models\CompanyProfile::where('company_name', $this->name)->first();
    }

    // 招待リレーション（既存）
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

    /** 
     * ★ 修正：ロゴURLを堅牢に解決
     * - http(s) はそのまま
     * - 'storage/...','public/...' を正規化
     * - disk('public') と public_path の両方を探索
     * - 見つからなければ NoImage
     */
    public function getLogoUrlAttribute(): ?string
    {
        // 候補（companies.* → profiles.*）
        $candidates = [];
        foreach ([
            $this->getRawLogoPathFromCompanies(),
            optional($this->profile())->logo_path ?? null,
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

            // storage/xxx（= public/storage/... を直接指す）ならそのまま返す
            if (Str::startsWith($p, 'storage/')) {
                return asset($p);
            }

            // storage/app/public にある？
            if (Storage::disk('public')->exists($p)) {
                return Storage::disk('public')->url($p); // /storage/xxx
            }

            // public 直下？
            if (file_exists(public_path($p))) {
                return asset($p);
            }
        }

        // なければ NoImage（実ファイル名に合わせて）
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
       完了プロフィール企業のみ取得（既存）
       ================================ */
    public function scopeWithCompletedProfile($query)
    {
        if (!Schema::hasTable('company_profiles')) return $query;

        $cpNameCol = Schema::hasColumn('company_profiles', 'company_name')
            ? 'company_name'
            : (Schema::hasColumn('company_profiles', 'name') ? 'name' : null);

        if (!$cpNameCol || !Schema::hasColumn('company_profiles', 'is_completed')) {
            return $query;
        }

        return $query
            ->join('company_profiles as cp', "cp.$cpNameCol", '=', 'companies.name')
            ->where('cp.is_completed', true)
            ->select('companies.*');
    }
}
