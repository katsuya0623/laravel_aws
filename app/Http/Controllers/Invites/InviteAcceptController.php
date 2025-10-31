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
use Illuminate\Support\Carbon;

class InviteAcceptController extends Controller
{
    /** 受諾フォーム表示 */
    public function show(string $token)
    {
        if (! Schema::hasTable('company_invitations')) {
            abort(404);
        }

        $inv = $this->findInvitationByToken($token);
        if (! $inv) {
            return redirect()->route('invites.expired');
        }

      // ▼ 期限切れ & ステータス検証（pending のみ許可）
        if ($this->isInvitationExpired($inv) || !$this->isInvitationPending($inv)) {
            return redirect()->route('invites.expired'); }

        $email = $this->resolveInvitationEmail($inv);

        $companyName = null;
        if (property_exists($inv, 'company_id') && $inv->company_id) {
            if ($company = Company::find($inv->company_id)) {
                $companyName = $company->name ?? $company->company_name ?? null;
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

    /** 受諾処理 */
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

        // 期限切れ & ステータス検証（pending のみ許可）
        if ($this->isInvitationExpired($inv) || !$this->isInvitationPending($inv)) {
            return redirect()->route('invites.expired');
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
                $user->name = ($company->name ?? $company->company_name ?? 'Company User');
            }
            $user->password = Hash::make($data['password']);

            // 2) メール確認済みに
            if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            // 3) 会社ロール付与
            $user->role = 'company';
            if (property_exists($user, 'is_active')) {
                $user->is_active = true;
            }

            // ★ company_id を必ず付与（あれば）
            if (Schema::hasColumn('users','company_id')) {
                $user->company_id = $company->id;
            }
            $user->save();

            if (method_exists($user, 'syncRoles')) {
                try { $user->syncRoles(['company']); } catch (\Throwable $e) {}
            }

            // 4) 会社へ確実に紐付け（pivot等）
            $this->attachUserToCompany($company, $user);

            // 5) CompanyProfile を「存在するカラムだけ」で upsert（company_id 優先）
            if (Schema::hasTable('company_profiles')) {
                $cols = Schema::getColumnListing('company_profiles');

                // upsert のキー
                $key = [];
                if (in_array('company_id', $cols, true)) {
                    $key['company_id'] = $company->id;
                } elseif (in_array('user_id', $cols, true)) {
                    $key['user_id'] = $user->id;
                }

                if (!empty($key)) {
                    $payload = [];
                    // 名称カラム
                    if (in_array('company_name', $cols, true)) {
                        $payload['company_name'] = ($company->name ?? $company->company_name ?? null);
                    } elseif (in_array('name', $cols, true)) {
                        $payload['name'] = ($company->name ?? $company->company_name ?? null);
                    }
                    // 完了フラグ（存在する場合のみ）
                    if (in_array('is_completed', $cols, true)) {
                        $payload['is_completed'] = false;
                    }
                    // タイムスタンプ（存在する場合のみ）
                    if (in_array('created_at', $cols, true)) {
                        $payload['created_at'] = now();
                    }
                    if (in_array('updated_at', $cols, true)) {
                        $payload['updated_at'] = now();
                    }

                    DB::table('company_profiles')->updateOrInsert($key, $payload);
                }
            }

            // 6) 招待を accepted に
            $update = ['status' => 'accepted'];
            if (Schema::hasColumn('company_invitations', 'accepted_at')) {
                $update['accepted_at'] = now();
            }
           // 二重受諾防止：token クリア（カラムがある場合）
            if (Schema::hasColumn('company_invitations', 'token')) {
                $update['token'] = null;
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

        // 8) 会社情報入力へ
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
                $cols = Schema::getColumnListing('company_profiles');

                // 既存レコードを company_id / user_id の順で探索
                if (in_array('company_id', $cols, true)) {
                    $profileId = DB::table('company_profiles')->where('company_id', $company->id)->value('id');
                }
                if (! $profileId && in_array('user_id', $cols, true)) {
                    $profileId = DB::table('company_profiles')->where('user_id', $user->id)->value('id');
                }

                // 無ければ「存在するカラムだけ」で最小作成
                if (! $profileId) {
                    $payload = [];
                    if (in_array('user_id', $cols, true))      $payload['user_id'] = $user->id;
                    if (in_array('company_id', $cols, true))   $payload['company_id'] = $company->id;
                    if (in_array('company_name', $cols, true)) $payload['company_name'] = ($company->name ?? $company->company_name ?? null);
                    elseif (in_array('name', $cols, true))     $payload['name'] = ($company->name ?? $company->company_name ?? null);
                    if (in_array('is_completed', $cols, true)) $payload['is_completed'] = false;
                    if (in_array('created_at', $cols, true))   $payload['created_at'] = now();
                    if (in_array('updated_at', $cols, true))   $payload['updated_at'] = now();

                    $profileId = DB::table('company_profiles')->insertGetId($payload);
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
     /**
     * 期限切れ判定（expires_at があれば使う）
     */
    private function isInvitationExpired(object $inv): bool
    {
        if (property_exists($inv, 'expires_at') && !empty($inv->expires_at)) {
            $exp = $inv->expires_at instanceof \DateTimeInterface
                ? $inv->expires_at
                : \Illuminate\Support\Carbon::parse($inv->expires_at);
            return now()->greaterThan($exp);
        }
        return false; // 期限未設定は期限切れ扱いにしない
    }

    /**
     * 受諾可能判定（pending 以外はNG／token欠落やaccepted_atありもNG）
     */
    private function isInvitationPending(object $inv): bool
    {
        $status = property_exists($inv, 'status') ? strtolower((string) $inv->status) : null;
        if ($status && $status !== 'pending') {
            return false;
        }

        // tokenが空なら二重受諾などとみなしてNG（カラムがある場合のみ）
        if (\Illuminate\Support\Facades\Schema::hasColumn('company_invitations', 'token') && empty($inv->token)) {
            return false;
        }

        // 既に受諾済みならNG（カラムがある場合のみ）
        if (\Illuminate\Support\Facades\Schema::hasColumn('company_invitations', 'accepted_at') && !empty($inv->accepted_at)) {
            return false;
        }

        return true;
    }
}


