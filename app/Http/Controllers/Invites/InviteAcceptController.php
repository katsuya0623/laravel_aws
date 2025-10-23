<?php

namespace App\Http\Controllers\Invites;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
// ★ 追加
use App\Models\CompanyProfile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class InviteAcceptController extends Controller
{
    /** 招待受諾フォーム表示 */
    public function show(Request $request, string $token)
    {
        $inv = CompanyInvitation::where('token', $token)->first();
        if (! $inv) {
            return redirect()->route('invites.expired');
        }

        if ($inv->status !== 'pending' || now()->greaterThan($inv->expires_at)) {
            return redirect()->route('invites.expired');
        }

        return view('invites.accept', [
            'token'        => $token,
            'email'        => $inv->email,
            'company_name' => $inv->company_name,
        ]);
    }

    /** 受諾完了（パスワード設定） */
    public function complete(Request $request, string $token)
    {
        $inv = CompanyInvitation::where('token', $token)->first();
        if (! $inv || $inv->status !== 'pending' || now()->greaterThan($inv->expires_at)) {
            return redirect()->route('invites.expired');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // ユーザー作成 or 取得
        $user = User::firstOrNew(['email' => $inv->email]);
        if (! $user->exists) {
            $user->name = $inv->company_name; // 仮の表示名
        }
        $user->password  = Hash::make($data['password']);
        $user->role      = 'company';
        $user->is_active = true;
        $user->save();

        // ====== 会社に紐付け（環境差異に強い実装） ======
        $company = Company::find($inv->company_id);
        if ($company) {
            $attached = false;

            // 1) リレーション優先
            if (method_exists($company, 'users')) {
                try {
                    if (! $company->users()->where('users.id', $user->id)->exists()) {
                        $company->users()->syncWithoutDetaching([$user->id]);
                    }
                    $attached = true;
                } catch (\Throwable $e) {
                    // フォールバックへ
                }
            }

            // 2) users.company_id
            if (! $attached && Schema::hasColumn($user->getTable(), 'company_id')) {
                if (! isset($user->company_id) || (int)$user->company_id !== (int)$company->id) {
                    $user->forceFill(['company_id' => $company->id])->save();
                }
                $attached = true;
            }

            // 3) ピボット候補
            if (! $attached) {
                $pivotCandidates = ['company_user', 'company_users', 'companies_users'];
                $companyCols     = ['company_id', 'companyId', 'companyID'];
                $userCols        = ['user_id', 'userId', 'userID'];
                foreach ($pivotCandidates as $tbl) {
                    if (! Schema::hasTable($tbl)) continue;
                    $cCol = collect($companyCols)->first(fn ($c) => Schema::hasColumn($tbl, $c));
                    $uCol = collect($userCols)->first(fn ($c) => Schema::hasColumn($tbl, $c));
                    if ($cCol && $uCol) {
                        $exists = DB::table($tbl)->where($cCol, $company->id)->where($uCol, $user->id)->exists();
                        if (! $exists) {
                            DB::table($tbl)->insert([$cCol => $company->id, $uCol => $user->id]);
                        }
                        $attached = true;
                        break;
                    }
                }
            }

            // 4) companies.user_id
            if (! $attached && Schema::hasColumn($company->getTable(), 'user_id')) {
                if ((int)($company->user_id ?? 0) !== (int)$user->id) {
                    $company->forceFill(['user_id' => $user->id])->save();
                }
            }

            // 会社名自動補完（Company 側にも反映しておく）
            $nameCols = collect(['name', 'company_name'])
                ->filter(fn ($c) => Schema::hasColumn($company->getTable(), $c));

            $hasAny = $nameCols->contains(fn ($c) => filled($company->{$c} ?? null));
            if (! $hasAny && filled($inv->company_name)) {
                $fill = [];
                foreach ($nameCols as $c) {
                    $fill[$c] = $inv->company_name;
                }
                if ($fill) {
                    $company->forceFill($fill)->save();
                }
            }
        }

        // ====== ★ CompanyProfile にも書き込んで編集画面の初期値にする ======
        $profile = CompanyProfile::firstOrNew(['user_id' => $user->id]);
        if (blank($profile->company_name) && filled($inv->company_name)) {
            $profile->company_name = $inv->company_name;
        }
        $profile->save();

        // ====== 招待を確定（accepted_at が無くてもOK） ======
        $payload = ['status' => 'accepted'];
        if (Schema::hasColumn($inv->getTable(), 'accepted_at')) {
            $payload['accepted_at'] = now();
        }
        $inv->forceFill($payload)->save();

        // ====== メール検証を済にする（MustVerifyEmail の時だけ） ======
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            if (Schema::hasColumn($user->getTable(), 'email_verified_at')) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            event(new Verified($user));
        }

        // 最新状態でログイン
        Auth::guard('web')->logout();
        $user->refresh();
        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        // 入力値も保険でフラッシュ
        $prefillName = $profile->company_name ?: $inv->company_name;
        $old = [
            'name'                 => $prefillName,
            'company_name'         => $prefillName,
            'company.name'         => $prefillName,
            'company.company_name' => $prefillName,
        ];

        return redirect()
            ->route('onboarding.company.edit') // ← 編集画面(= user.company.edit)への導線でOK
            ->withInput($old)
            ->with('status', 'アカウントを有効化しました。まずは企業プロフィールを設定してください。');
    }
}
