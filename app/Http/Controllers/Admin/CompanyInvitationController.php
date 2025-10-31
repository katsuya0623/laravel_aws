<?php
// app/Http/Controllers/Admin/CompanyInvitationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Notifications\CompanyInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

// ★ 追加：sendResetByEmail に必要
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class CompanyInvitationController extends Controller
{
    /** 招待を作成してメール送信 */
    public function store(Request $request)
    {
        $data = $request->validate([
            'email'        => ['required', 'email'],
            'company_name' => ['required', 'string', 'max:255'],
        ]);

        // ① slug を安全に生成（日本語でも空にならないように）
        $base = Str::slug($data['company_name']);
        $slug = $base !== '' ? $base : 'c-' . Str::lower(Str::random(8));
        $try  = 0;
        while (Company::where('slug', $slug)->exists()) {
            $try++;
            $slug = ($base !== '' ? $base : 'c') . '-' . ($try === 1 ? Str::lower(Str::random(6)) : $try);
        }

        // ② Company 作成（最小項目）
        $company = Company::create([
            'name' => $data['company_name'],
            'slug' => $slug,
        ]);

        // ③ 招待レコード
        $expiresDays = 7; // ← 表示用に残してOK（メール文言などに使っている想定）

        $invitation = CompanyInvitation::create([
            'email'        => $data['email'],
            'company_name' => $data['company_name'],
            'company_id'   => $company->id,
            'token'        => (string) Str::uuid(),
            'expires_at'   => now()->addDays($expiresDays),  // ← ★ 本番仕様（7日間）
            'status'       => 'pending',
            'invited_by'   => $request->user()?->id,
        ]);


        // ④ 受諾URL（署名付き）
        $acceptUrl = URL::temporarySignedRoute(
            'invites.accept',
            $invitation->expires_at,              // ← ここも DBに入れた expires_at を使う
            ['token' => $invitation->token]
        );


        // ⑤ 招待メール送信
        Notification::route('mail', $invitation->email)
            ->notify(new CompanyInvitationNotification($acceptUrl, $expiresDays));

        return response()->noContent();
    }

    /**
     * ★ 管理画面：会社×メールでパスワードリセットを送信
     * - ユーザーが居なければ作成（companyロール付与）
     * - 会社へ紐付け（pivot or companies.user_id）
     * - プロファイルの下地を用意（任意）
     * - Password::sendResetLink を送信
     *
     * ルート: POST admin/companies/{company}/send-reset-by-email
     */
    public function sendResetByEmail(Request $request, Company $company)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [], ['email' => '送信先メールアドレス']);

        $email = strtolower(trim($data['email']));

        DB::transaction(function () use ($email, $company) {
            // 1) ユーザー作成/取得
            $user = User::firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->name     = $company->name ?? 'Company User';
                $user->password = Hash::make(Str::random(32)); // 後で本人が再設定
            }

            // 2) companyロールを強制
            if (property_exists($user, 'role')) $user->role = 'company';
            if (property_exists($user, 'is_active')) $user->is_active = true;
            $user->save();
            if (method_exists($user, 'syncRoles')) {
                try {
                    $user->syncRoles(['company']);
                } catch (\Throwable $e) {
                }
            }

            // 3) 会社との紐付け
            $linked = false;
            if (
                Schema::hasTable('company_user')
                && Schema::hasColumn('company_user', 'company_id')
                && Schema::hasColumn('company_user', 'user_id')
            ) {
                DB::table('company_user')->updateOrInsert(
                    ['company_id' => $company->id, 'user_id' => $user->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
                $linked = true;
            }
            if (! $linked && Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
                DB::table('companies')->where('id', $company->id)->update([
                    'user_id'    => $user->id,
                    'updated_at' => now(),
                ]);
                $linked = true;
            }
            if (! $linked) {
                Log::warning('sendResetByEmail: cannot link user to company', [
                    'company_id' => $company->id,
                    'user_id'    => $user->id,
                ]);
            }

            // 4) プロファイル下地（存在しなければ）
            if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles', 'user_id')) {
                DB::table('company_profiles')->updateOrInsert(
                    ['user_id' => $user->id],
                    ['company_name' => $company->name, 'is_completed' => false, 'created_at' => now(), 'updated_at' => now()]
                );
            }

            // 5) リセットリンク送信
            Password::broker('users')->sendResetLink(['email' => $user->email]);
        });

        return back()->with('status', 'パスワードリセットメールを送信しました。');
    }

    // （必要なら）再送/取消は従来実装のままでOK
}
