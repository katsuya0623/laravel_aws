<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class RoleResolver
{
    /** @return 'admin'|'company'|'enduser' */
    public static function resolve($user): string
    {
        if (!$user) return 'enduser';

        // 0) users.role があれば最優先で採用（値は正規化）
        if (Schema::hasColumn($user->getTable(), 'role') && filled($user->role)) {
            return self::normalizeRole($user->role);
        }

        // 1) 管理者メール（roles.php の admins）
        $admins = Config::get('roles.admins', []);
        if (in_array($user->email, $admins, true)) {
            return 'admin';
        }

        // 2) 企業アカ判定（roles.php の is_company_resolver）
        $cfg       = Config::get('roles.is_company_resolver', []);
        $byCol     = Arr::get($cfg, 'by_column'); // 例: company_id
        $byDomain  = Arr::get($cfg, 'by_domain'); // 例: example.co.jp

        // 2-1) カラムがあり、値が入っていれば企業扱い
        if ($byCol && Schema::hasColumn($user->getTable(), $byCol) && filled($user->{$byCol} ?? null)) {
            return 'company';
        }

        // 2-2) メールドメインで企業扱い（任意設定）
        if ($byDomain && str_ends_with(strtolower($user->email), '@'.strtolower($byDomain))) {
            return 'company';
        }

        // 3) 既定はエンドユーザー
        return 'enduser';
    }

    /** 受け取ったrole文字列を 'admin'|'company'|'enduser' に正規化 */
    private static function normalizeRole(string $raw): string
    {
        $v = strtolower(trim($raw));
        return match ($v) {
            'admin', 'administrator', 'ops', 'owner' => 'admin',
            'company', 'corp', 'business'            => 'company',
            'enduser', 'user', 'member'              => 'enduser',
            default                                   => 'enduser',
        };
    }
}
