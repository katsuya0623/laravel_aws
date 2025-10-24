<?php

namespace App\Http\Controllers\Invites;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class InviteAcceptController extends Controller
{
    /**
     * 受諾フォーム表示（GET /invites/accept/{token}）
     * ルート名: invites.accept
     */
    public function show(string $token)
    {
        if (! Schema::hasTable('company_invitations')) {
            abort(404);
        }

        $inv = $this->findInvitationByToken($token);
        if (! $inv) {
            return redirect()->route('invites.expired');
        }

        if (property_exists($inv, 'expires_at') && ! empty($inv->expires_at)) {
            if (now()->greaterThan($inv->expires_at)) {
                return redirect()->route('invites.expired');
            }
        }

        $email = $this->resolveInvitationEmail($inv);

        $companyName = null;
        if (property_exists($inv, 'company_id') && $inv->company_id) {
            if ($company = Company::find($inv->company_id)) {
                $companyName = $company->name;
            }
        }
        if (! $companyName && property_exists($inv, 'company_name') && ! empty($inv->company_name)) {
            $companyName = $inv->company_name;
        }

        return view('invites.accept', [
            'token'        => $token,
            'email'        => $email,
            'company_name' => $companyName,
        ]);
    }

    /**
     * 受諾処理（POST /invites/accept/{token}）
     * ルート名: invites.accept.post
     * - パスワード設定
     * - ユーザー作成/取得
     * - 会社ロール強制付与＆確実な自動紐付け
     * - 招待ステータス更新
     * - そのままログイン→会社プロフィール編集へ
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required'  => 'パスワードを入力してください。',
            'password.min'       => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => '確認用パスワードと一致しません。',
        ]);

        if (! Schema::hasTable('company_invitations')) {
            return back()->withErrors(['invite' => '招待情報が見つかりませんでした。']);
        }

        $inv = $this->findInvitationByToken($token);
        if (! $inv) {
            return redirect()->route('invites.expired');
        }

        if (property_exists($inv, 'expires_at') && ! empty($inv->expires_at)) {
            if (now()->greaterThan($inv->expires_at)) {
                return redirect()->route('invites.expired');
            }
        }

        if (! property_exists($inv, 'company_id') || empty($inv->company_id)) {
            return back()->withErrors(['invite' => '招待に会社情報がありません。']);
        }

        $company = Company::find($inv->company_id);
        if (! $company) {
            return back()->withErrors(['invite' => '対象の会社が存在しません。']);
        }

        $email = $this->resolveInvitationEmail($inv) ?: trim((string)($company->email ?? ''));
        if (! $email) {
            return back()->withErrors(['email' => '送信先メールアドレスを特定できません。']);
        }

        DB::transaction(function () use ($data, $email, $company, $inv) {
            // 1) ユーザー作成/取得
            $user = User::firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->name = $company->name ?? 'Company User';
            }
            $user->password = Hash::make($data['password']);

            // 2) メール確認済みに
            if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            // 3) 会社ロールを**無条件に上書き**（Spatieあれば同期）
            $user->role = 'company';
            if (property_exists($user, 'is_active')) {
                $user->is_active = true;
            }
            $user->save();

            if (method_exists($user, 'syncRoles')) {
                try { $user->syncRoles(['company']); } catch (\Throwable $e) {}
            }

            // 4) 会社へ **確実に** 紐付け
            $this->attachUserToCompany($company, $user);

            // 5) CompanyProfile を用意（未完了フラグで）
            if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles','user_id')) {
                DB::table('company_profiles')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'company_name' => $company->name,
                        'is_completed' => false,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]
                );
            }

            // 6) 招待を accepted に
            $update = ['status' => 'accepted'];
            if (Schema::hasColumn('company_invitations', 'accepted_at')) {
                $update['accepted_at'] = now();
            }
            DB::table('company_invitations')->where('id', $inv->id)->update($update);

            // 7) そのままログイン
            auth()->login($user);

            Log::info('Invite accepted', [
                'company_id' => $company->id,
                'user_id'    => $user->id,
                'email'      => $user->email,
            ]);
        });

        // 8) 会社情報入力へ強制遷移（/onboarding/company を優先）
        $target = Route::has('onboarding.company.edit')
            ? route('onboarding.company.edit')
            : url('/onboarding/company');

        return redirect($target)->with('onboarding', true);
    }

    /* ======================= Helpers ======================= */

    private function findInvitationByToken(string $token): ?object
    {
        $candidates = array_values(array_filter([
            Schema::hasColumn('company_invitations', 'token') ? 'token' : null,
            Schema::hasColumn('company_invitations', 'uuid')  ? 'uuid'  : null,
            Schema::hasColumn('company_invitations', 'code')  ? 'code'  : null,
        ]));

        foreach ($candidates as $col) {
            $inv = DB::table('company_invitations')->where($col, $token)->first();
            if ($inv) return $inv;
        }
        return null;
    }

    private function resolveInvitationEmail(object $inv): ?string
    {
        foreach (['email', 'invited_email', 'invitee_email', 'recipient_email'] as $c) {
            if (property_exists($inv, $c) && ! empty($inv->{$c})) {
                return trim((string) $inv->{$c});
            }
        }
        return null;
    }

    /**
     * 会社ひも付けを「どれか1つは必ず作る」堅牢実装
     */
    private function attachUserToCompany(Company $company, User $user): void
    {
        $linked = false;

        // 1) 標準 pivot: company_user(company_id, user_id)
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
                Log::warning('attachUserToCompany: pivot(company_id,user_id) insert failed', [
                    'company_id' => $company->id, 'user_id' => $user->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        // 2) 旧構成: company_user(company_profile_id, user_id)
        if (! $linked
            && Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'company_profile_id')) {

            $profileId = null;
            if (Schema::hasTable('company_profiles')) {
                if (Schema::hasColumn('company_profiles','company_id')) {
                    $profileId = DB::table('company_profiles')->where('company_id', $company->id)->value('id');
                }
                if (! $profileId && Schema::hasColumn('company_profiles','user_id')) {
                    $profileId = DB::table('company_profiles')->where('user_id', $user->id)->value('id');
                }
                if (! $profileId && Schema::hasColumn('company_profiles','user_id')) {
                    // 必要最小限で新規作成
                    $profileId = DB::table('company_profiles')->insertGetId([
                        'user_id'      => $user->id,
                        'company_name' => $company->name,
                        'is_completed' => false,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }

            if ($profileId) {
                try {
                    DB::table('company_user')->updateOrInsert(
                        ['company_profile_id' => $profileId, 'user_id' => $user->id],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                    $linked = true;
                } catch (\Throwable $e) {
                    Log::warning('attachUserToCompany: pivot(company_profile_id,user_id) insert failed', [
                        'company_id' => $company->id, 'user_id' => $user->id, 'profile_id' => $profileId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // 3) 単一FK: companies.user_id
        if (! $linked && Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
            try {
                DB::table('companies')->where('id', $company->id)->update([
                    'user_id'    => $user->id,
                    'updated_at' => now(),
                ]);
                $linked = true;
            } catch (\Throwable $e) {
                Log::warning('attachUserToCompany: companies.user_id update failed', [
                    'company_id' => $company->id, 'user_id' => $user->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $linked) {
            Log::error('attachUserToCompany: no link created', [
                'company_id' => $company->id,
                'user_id'    => $user->id,
            ]);
        }
    }
}
