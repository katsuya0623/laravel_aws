<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\CompanyProfile;
// スポンサー逆参照で使う（FQCNでもOK）
use App\Models\Post;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_sponsor', // ★ 追加：管理画面で更新できるように
        // companies 側にロゴ列があるなら 'logo','logo_path' を追加してOK
    ];

    // ★ 追加：booleanとして扱う
    protected $casts = [
        'is_sponsor' => 'boolean',
    ];

    /** $company->logo_url が使えるようにする */
    protected $appends = ['logo_url'];

    /**
     * Admin側の company_profiles を突合
     * - slug は無い想定なので、companies.name ≒ company_profiles.company_name で突合
     */
    public function profile(): ?CompanyProfile
    {
        if (empty($this->name)) return null;
        return CompanyProfile::where('company_name', $this->name)->first();
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

        // 4) 最後の保険（任意パスに変更OK）
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

    // ===== ここからスポンサー機能の追加 =====

    /**
     * スポンサー企業だけに絞るスコープ
     */
    public function scopeSponsor($query)
    {
        return $query->where('is_sponsor', true);
    }

    /**
     * この会社をスポンサーに持つ記事一覧（posts.sponsor_company_id = companies.id）
     */
    public function sponsoredPosts()
    {
        return $this->hasMany(Post::class, 'sponsor_company_id');
    }
}
