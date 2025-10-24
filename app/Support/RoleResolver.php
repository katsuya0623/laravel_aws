<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $norm = self::normalizeRole($user->role);
            if ($norm !== 'enduser') return $norm; // company/admin はここで確定
            // enduser の場合は後続の“証跡”チェックに委ねる
        }

        // 2) 管理者メール（config/roles.php）
        $admins = Config::get('roles.admins', []);
        if (in_array($user->email, $admins, true)) {
            return 'admin';
        }

        // 3) Eloquent リレーションがあれば company 優先
        //    companies() / companyProfiles() どちらかが存在して1件でもあれば company
        foreach (['companies', 'companyProfiles'] as $rel) {
            if (method_exists($user, $rel)) {
                try {
                    if ($user->{$rel}()->exists()) return 'company';
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // 3') 会社所属の“DB証跡”で判定（どれか1つでも true なら company）
        if (self::hasCompanyEvidence($user)) {
            return 'company';
        }

        // 4) users テーブルの外部キーで判定（任意設定）
        $cfg   = Config::get('roles.is_company_resolver', []);
        $byCol = Arr::get($cfg, 'by_column'); // 例: 'company_id'
        if ($byCol && Schema::hasColumn($user->getTable(), $byCol) && filled($user->{$byCol} ?? null)) {
            return 'company';
        }

        // 5) メールドメインで企業扱い（任意設定）
        $byDomain = Arr::get($cfg, 'by_domain'); // 例: 'example.co.jp'
        if ($byDomain && str_ends_with(strtolower($user->email), '@'.strtolower($byDomain))) {
            return 'company';
        }

        // 6) 既定はエンドユーザー
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

    /**
     * company_user / companies.user_id / company_profiles.user_id の
     * いずれかが自分を指していれば true
     */
    private static function hasCompanyEvidence(User $user): bool
    {
        try {
            // pivot: company_user(company_id,user_id)
            if (Schema::hasTable('company_user')
                && Schema::hasColumn('company_user', 'company_id')
                && Schema::hasColumn('company_user', 'user_id')) {
                if (DB::table('company_user')->where('user_id', $user->id)->exists()) {
                    return true;
                }
            }

            // companies.user_id
            if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
                if (DB::table('companies')->where('user_id', $user->id)->exists()) {
                    return true;
                }
            }

            // company_profiles.user_id
            if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles', 'user_id')) {
                if (DB::table('company_profiles')->where('user_id', $user->id)->exists()) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // 失敗時は証跡なし扱い
        }
        return false;
    }
}
