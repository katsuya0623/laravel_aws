<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class RoleResolver
{
    /** @return 'admin'|'company'|'enduser' */
    public static function resolve($user): string
    {
        if (! $user instanceof User) return 'enduser';

        // 0) Spatie 権限があれば最優先
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('admin'))   return 'admin';
            if ($user->hasRole('company')) return 'company';
        }

        // 1) users.role カラムがあれば採用（正規化）
        if (Schema::hasColumn($user->getTable(), 'role') && filled($user->role)) {
            return self::normalizeRole($user->role);
        }

        // 2) 管理者メール（config/roles.php）
        $admins = Config::get('roles.admins', []);
        if (in_array($user->email, $admins, true)) {
            return 'admin';
        }

        // 3) 会社所属を見て company 優先
        //    3-1) users ↔ companies の pivot がある場合（慣例: companies()/companyProfiles() どちらでもOKに）
        foreach (['companies', 'companyProfiles'] as $rel) {
            if (method_exists($user, $rel)) {
                try {
                    if ($user->{$rel}()->exists()) return 'company';
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        //    3-2) users テーブルに company_id 等の外部キーがある場合（設定で指定）
        $cfg   = Config::get('roles.is_company_resolver', []);
        $byCol = Arr::get($cfg, 'by_column'); // 例: 'company_id'
        if ($byCol && Schema::hasColumn($user->getTable(), $byCol) && filled($user->{$byCol} ?? null)) {
            return 'company';
        }

        // 4) メールドメインで企業扱い（任意設定）
        $byDomain = Arr::get($cfg, 'by_domain'); // 例: 'example.co.jp'
        if ($byDomain && str_ends_with(strtolower($user->email), '@'.strtolower($byDomain))) {
            return 'company';
        }

        // 5) 既定はエンドユーザー
        return 'enduser';
    }

    private static function normalizeRole(string $raw): string
    {
        $v = strtolower(trim($raw));
        return match ($v) {
            'admin','administrator','ops','owner' => 'admin',
            'company','corp','business'           => 'company',
            'enduser','user','member'             => 'enduser',
            default                                => 'enduser',
        };
    }
}
