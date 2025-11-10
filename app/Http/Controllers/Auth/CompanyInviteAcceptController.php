<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password; // 追加
use Illuminate\Support\Facades\Auth;     // 追加

class CompanyInviteAcceptController extends Controller
{
    /**
     * 招待リンク受諾：
     * - token で招待を特定
     * - email と company_id を解決
     * - User を作成/取得
     * - Company に自動紐付け（pivot/company_user or companies.user_id）
     * - 招待ステータスを accepted に更新
     * - 初回: パスワード設定画面へ / 既存: 自動ログインして onboarding へ
     */
    public function accept(string $token): RedirectResponse
    {
        // 1) 招待レコードの特定
        if (! Schema::hasTable('company_invitations')) {
            abort(404);
        }

        $tokenCols = array_values(array_filter([
            Schema::hasColumn('company_invitations', 'token') ? 'token' : null,
            Schema::hasColumn('company_invitations', 'uuid')  ? 'uuid'  : null,
            Schema::hasColumn('company_invitations', 'code')  ? 'code'  : null,
        ]));
        if (empty($tokenCols)) abort(404);

        $inv = null;
        foreach ($tokenCols as $col) {
            $inv = DB::table('company_invitations')->where($col, $token)->first();
            if ($inv) break;
        }
        if (! $inv) abort(404);

        // 2) company_id / email を解決
        if (! property_exists($inv, 'company_id') || ! $inv->company_id) abort(404);
        $company = Company::find($inv->company_id);
        if (! $company) abort(404);

        $email = null;
        foreach (['email', 'invited_email', 'invitee_email', 'recipient_email'] as $c) {
            if (property_exists($inv, $c) && ! empty($inv->{$c})) {
                $email = trim((string) $inv->{$c});
                break;
            }
        }
        if (! $email && ! empty($company->email)) {
            $email = trim((string) $company->email);
        }
        if (! $email) {
            return redirect()->route('login')->with('error', '招待のメールアドレスが特定できませんでした。');
        }

        // 3) ユーザーを作成/取得
        $user = User::firstOrNew(['email' => $email]);
        $newlyCreated = ! $user->exists;

        if ($newlyCreated) {
            $user->name     = $company->name ?? 'Company User';
            // 仮パス（初回設定で即変更するためランダムでOK）
            $user->password = bcrypt(Str::random(24));
            if (method_exists($user, 'assignRole')) {
                try { $user->assignRole('company'); } catch (\Throwable $e) {}
            }
            $user->role = $user->role ?? 'company';
            if (property_exists($user, 'is_active')) $user->is_active = true;
            $user->save();
            Log::info('INVITE: user created', ['id' => $user->id, 'email' => $user->email]); // ★追加（デバッグ）
        } else {
            if (property_exists($user, 'is_active')) {
                $user->is_active = true;
                $user->save();
            }
            Log::info('INVITE: user exists', ['id' => $user->id, 'email' => $user->email]); // ★追加（デバッグ）
        }

        // 4) 会社へ自動紐付け
        $this->attachUserToCompany($company, $user);

        // 5) 招待状態を accepted に更新
        $update = ['status' => 'accepted'];
        if (Schema::hasColumn('company_invitations', 'accepted_at')) {
            $update['accepted_at'] = now();
        }
        DB::table('company_invitations')->where('id', $inv->id)->update($update);

        Log::info('Company invitation accepted', [
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'email'      => $user->email,
        ]);

        // 6) 分岐：初回はパスワード設定へ、既存はログインしてオンボーディングへ
        if ($newlyCreated) {
            $resetToken = Password::broker()->createToken($user);
            return redirect()->route('password.reset', [
                'token' => $resetToken,
                'email' => $user->email,
            ])->with('status', 'はじめにパスワードを設定してください。');
        }

        Auth::login($user);
        // ★ ここだけルート名を web.php と一致させる
        return redirect()->route('onboarding.company.edit')
            ->with('status', 'ログインしました。オンボーディングを続けてください。');
    }

    /**
     * 実スキーマに合わせて安全に紐付け
     */
    private function attachUserToCompany(Company $company, User $user): void
    {
        // pivot: company_user（company_id & user_id）
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

        // 旧構成: company_user.company_profile_id 経由
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

        // 単一外部キー: companies.user_id
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
            if ((int) ($company->user_id ?? 0) !== (int) $user->id) {
                $company->user_id = $user->id;
                $company->save();
            }
        }
    }
}
