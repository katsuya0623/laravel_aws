<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'slug',
        'description',
        // スポンサー機能は不使用のため is_sponsor は外しました
        // ロゴ系の列を使う場合は 'logo', 'logo_path' などをここに追加してください
    ];

    // 追加のキャストは現状なし（is_sponsor も削除）

    /** 会社ロゴURLを自動で付与 */
    protected $appends = ['logo_url'];

    /* =======================================================
     |  リレーション
     * =======================================================*/

    /**
     * 会社に紐づくユーザー（企業アカウント）
     * ピボット: company_user（company_id, user_id）を想定
     */
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class)->withTimestamps();
    }

    /* =======================================================
     |  会社プロフィール (既存の突合仕様のまま)
     * =======================================================*/

    /**
     * Admin側の company_profiles を突合
     * - slug は無い想定なので、companies.name ≒ company_profiles.company_name で突合
     */
    public function profile(): ?\App\Models\CompanyProfile
    {
        if (empty($this->name)) return null;
        return \App\Models\CompanyProfile::where('company_name', $this->name)->first();
    }

    /**
     * ロゴURL解決（companies → company_profiles の順で参照）
     */
    public function getLogoUrlAttribute(): string
    {
        // 1) companies 側のカラム優先（もし存在するなら）
        $path = $this->getRawLogoPathFromCompanies();

        // 2) 無ければ company_profiles 側の logo_path を参照
        if (!$path) {
            if ($p = $this->profile()) {
                $path = $p->logo_path ?? null;
            }
        }

        // 3) URL 解決
        if ($path) {
            if (preg_match('#^https?://#', $path)) {
                return $path; // 既にフルURL
            }
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path); // /storage/...
            }
            if (file_exists(public_path($path))) {
                return asset($path); // public直下
            }
        }

        // 4) 最後の保険
        return asset('images/noimage.svg');
    }

    /**
     * companies 表にロゴ系カラムがあれば取得（無ければ null）
     */
    private function getRawLogoPathFromCompanies(): ?string
    {
        // よくある列名を順にチェック（存在しない場合は null）
        return $this->logo_path
            ?? $this->logo
            ?? $this->thumbnail_path
            ?? null;
    }

    /* =======================================================
     |  （スポンサー関連は不使用のため削除）
     *  - scopeSponsor()
     *  - sponsoredPosts()
     * =======================================================*/
}
