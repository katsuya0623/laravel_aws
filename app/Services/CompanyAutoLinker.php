<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CompanyAutoLinker
{
    /**
     * 会社プロフィール保存後に呼ぶ。
     * - 招待テーブル or 会社名から Company を解決（必要に応じて新規作成も可）
     * - pivot / companies.user_id のどちらか「必ず」作る
     * - users.role を company に矯正
     *
     * @param  User $user
     * @param  array{company_name?:string, create_if_missing?:bool} $opts
     */
    public static function link(User $user, array $opts = []): void
    {
        // --- 会社候補を解決 ---
        $company = self::resolveCompany($user, $opts);
        if (! $company) {
            if (!empty($opts['create_if_missing']) && Schema::hasTable('companies')) {
                $company = new Company();
                $company->name = $opts['company_name'] ?? '(未設定)';
                if (Schema::hasColumn($company->getTable(), 'user_id')) {
                    $company->user_id = $user->id;
                }
                $company->save();
            } else {
                Log::warning('CompanyAutoLinker: company not resolved', ['user_id' => $user->id]);
                return;
            }
        }

        // --- ロール矯正 ---
        $user->role = 'company';
        if (property_exists($user, 'is_active')) $user->is_active = true;
        $user->save();
        if (method_exists($user, 'syncRoles')) {
            try { $user->syncRoles(['company']); } catch (\Throwable $e) {}
        }

        // --- 紐付け（どれか1つは必ず作る） ---
        $linked = false;

        // pivot: company_user(company_id, user_id)
        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'company_id')
            && Schema::hasColumn('company_user', 'user_id')) {
            try {
                DB::table('company_user')->updateOrInsert(
                    ['company_id' => $company->id, 'user_id' => $user->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
                $linked = true;
            } catch (\Throwable $e) {
                Log::warning('CompanyAutoLinker: pivot attach failed', [
                    'cid' => $company->id, 'uid' => $user->id, 'e' => $e->getMessage()
                ]);
            }
        }

        // companies.user_id にも反映（併用 or フォールバック）
        if (! $linked && Schema::hasTable('companies') && Schema::hasColumn('companies','user_id')) {
            try {
                DB::table('companies')->where('id', $company->id)->update([
                    'user_id'    => $user->id,
                    'updated_at' => now(),
                ]);
                $linked = true;
            } catch (\Throwable $e) {
                Log::warning('CompanyAutoLinker: companies.user_id update failed', [
                    'cid' => $company->id, 'uid' => $user->id, 'e' => $e->getMessage()
                ]);
            }
        }

        if (! $linked) {
            Log::error('CompanyAutoLinker: no link created', [
                'cid' => $company->id, 'uid' => $user->id,
            ]);
        }
    }

    /** 会社候補の解決（招待→既存紐付け→名前照合の順） */
    private static function resolveCompany(User $user, array $opts = []): ?Company
    {
        // 1) 招待テーブルから
        if (Schema::hasTable('company_invitations')) {
            $inv = DB::table('company_invitations')
                ->where(function ($q) use ($user) {
                    $q->where('email', $user->email);
                    foreach (['invited_email','invitee_email','recipient_email'] as $c) {
                        if (Schema::hasColumn('company_invitations', $c)) {
                            $q->orWhere($c, $user->email);
                        }
                    }
                })
                ->orderByDesc('id')->first();
            if ($inv && !empty($inv->company_id)) {
                $c = Company::find($inv->company_id);
                if ($c) return $c;
            }
        }

        // 2) すでに自分に紐づく会社
        if (Schema::hasTable('companies') && Schema::hasColumn('companies','user_id')) {
            $existing = Company::where('user_id', $user->id)->first();
            if ($existing) return $existing;
        }

        // 3) 会社名で照合
        if (!empty($opts['company_name']) && Schema::hasTable('companies')) {
            $byName = Company::where('name', $opts['company_name'])->first();
            if ($byName) return $byName;
        }

        return null;
    }
}
