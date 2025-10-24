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
     * - 会社へ自動紐付け
     * - 招待ステータス更新
     * - ★ 企業ユーザーはメール検証済みにする（email_verified_at=now）
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

        $email = $this->resolveInvitationEmail($inv);
        if (! $email && ! empty($company->email)) {
            $email = trim((string) $company->email);
        }
        if (! $email) {
            return back()->withErrors(['email' => '送信先メールアドレスを特定できません。']);
        }

        // ユーザー作成/更新
        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->name = $company->name ?? 'Company User';
        }
        $user->password = Hash::make($data['password']);

        // ★ ここで企業ユーザーはメール検証済みにする
        if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        if (method_exists($user, 'assignRole')) {
            try { $user->assignRole('company'); } catch (\Throwable $e) {}
        }
        if (property_exists($user, 'role') && empty($user->role)) {
            $user->role = 'company';
        }
        if (property_exists($user, 'is_active')) {
            $user->is_active = true;
        }
        $user->save();

        // 会社へ自動紐付け
        $this->attachUserToCompany($company, $user);

        // 招待を accepted に
        $update = ['status' => 'accepted'];
        if (Schema::hasColumn('company_invitations', 'accepted_at')) {
            $update['accepted_at'] = now();
        }
        DB::table('company_invitations')->where('id', $inv->id)->update($update);

        Log::info('Invite accepted', [
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'email'      => $user->email,
        ]);

        // 任意：自動ログイン
        // auth()->login($user);

        return redirect()->route('dashboard')->with('status', '招待を受諾し、企業アカウントに紐づけました。');
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

    private function attachUserToCompany(Company $company, User $user): void
    {
        // 標準 pivot
        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'company_id')
            && Schema::hasColumn('company_user', 'user_id')) {

            $exists = DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->exists();

            if (! $exists) {
                DB::table('company_user')->insert([
                    'company_id' => $company->id,
                    'user_id'    => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return;
        }

        // 旧構成: company_profile 経由
        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'company_profile_id')
            && Schema::hasTable('company_profiles')
            && Schema::hasColumn('company_profiles', 'company_id')) {

            $profileId = DB::table('company_profiles')
                ->where('company_id', $company->id)
                ->value('id');

            if ($profileId) {
                $exists = DB::table('company_user')
                    ->where('company_profile_id', $profileId)
                    ->where('user_id', $user->id)
                    ->exists();

                if (! $exists) {
                    DB::table('company_user')->insert([
                        'company_profile_id' => $profileId,
                        'user_id'            => $user->id,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
                return;
            }
        }

        // 単一FK
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
            if ((int) ($company->user_id ?? 0) !== (int) $user->id) {
                $company->user_id = $user->id;
                $company->save();
            }
        }
    }
}
