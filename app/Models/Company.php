<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;   // ★ 追加

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
}
