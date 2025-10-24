<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;   // ★ 追加
use Illuminate\Support\Facades\Schema;          // ★ 追記：スキーマ判定に使う
use Illuminate\Database\Eloquent\Builder;       // ★ withInviteState 用

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

    // ★ ここから追加：保存直前バリデーション（どこから保存しても必ず効く）
    protected static function booted(): void
    {
        static::saving(function (Company $company) {
            $name = (string)($company->name ?? '');

            // （任意）Unicode 正規化：ext-intl があれば実行
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
    // ★ 追加ここまで

    /** リレーションなど既存のコードはそのまま ↓ */
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class)->withTimestamps();
    }

    public function profile(): ?\App\Models\CompanyProfile
    {
        if (empty($this->name)) return null;
        return \App\Models\CompanyProfile::where('company_name', $this->name)->first();
    }

    // ★ 追加：招待リレーション（company_id が無ければ company_name で紐付け）
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
        // フォールバック（テーブル無い場合は空 hasMany）
        return $this->hasMany(\App\Models\CompanyInvitation::class, 'company_id');
    }

    // ★ 追加：一覧に has_pending_invite（bool）を載せる
    public function scopeWithInviteState(Builder $query): Builder
    {
        return $query->withExists([
            'invitations as has_pending_invite' => fn ($q) => $q->where('status', 'pending'),
        ]);
    }

    public function getLogoUrlAttribute(): string
    {
        $path = $this->getRawLogoPathFromCompanies();

        if (!$path) {
            if ($p = $this->profile()) {
                $path = $p->logo_path ?? null;
            }
        }

        if ($path) {
            if (preg_match('#^https?://#', $path)) {
                return $path;
            }
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }

        return asset('images/noimage.svg');
    }

    private function getRawLogoPathFromCompanies(): ?string
    {
        return $this->logo_path
            ?? $this->logo
            ?? $this->thumbnail_path
            ?? null;
    }

    /* ================================
       ★ 追記：完了プロフィール企業のみ取得
       ================================ */
    public function scopeWithCompletedProfile($query)
    {
        // company_profiles が無ければ素通し
        if (!Schema::hasTable('company_profiles')) return $query;

        // company_profiles の会社名カラムを特定（company_name or name）
        $cpNameCol = Schema::hasColumn('company_profiles', 'company_name')
            ? 'company_name'
            : (Schema::hasColumn('company_profiles', 'name') ? 'name' : null);

        if (!$cpNameCol || !Schema::hasColumn('company_profiles', 'is_completed')) {
            return $query; // 必要カラムが無ければ素通し
        }

        // companies.name = company_profiles.company_name（or name）で突合し、完了のみ
        return $query
            ->join('company_profiles as cp', "cp.$cpNameCol", '=', 'companies.name')
            ->where('cp.is_completed', true)
            ->select('companies.*');
    }
}
