<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Models\CompanyProfile;

class EnsureCompanyProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        // ① 未ログインはスルー
        if (!Auth::check()) {
            return $next($request);
        }
        $user = Auth::user();

        // ② メール未認証はスルー（認証フローを邪魔しない）
        if (is_null($user->email_verified_at)) {
            return $next($request);
        }

        // ③ エンドユーザー／管理者はスルー
        if (($user->role ?? null) === 'enduser' || ($user->role ?? null) === null) {
            return $next($request);
        }
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        // ④ 企業ユーザー判定（hasRole or RoleResolver）
        $isCompany = false;
        if (method_exists($user, 'hasRole')) {
            $isCompany = $user->hasRole('company');
        }
        if (!$isCompany && class_exists(\App\Support\RoleResolver::class)) {
            try {
                $isCompany = (\App\Support\RoleResolver::resolve($user) === 'company');
            } catch (\Throwable $e) {
                // noop
            }
        }
        if (!$isCompany) {
            return $next($request);
        }

        // ⑤ 静的アセットは除外
        $path = ltrim($request->path(), '/');
        if (preg_match('#^(storage|assets|build|vendor|images|img|css|js)/#', $path)) {
            return $next($request);
        }

        // ⑥ ホワイトリスト（編集・保存・認証系など自体はブロックしない）
        $whitelist = [
            'onboarding.company.edit', 'onboarding.company.update',
            'user.company.edit', 'user.company.update',
            'company.profile.first', 'company.profile.first.store',
            'login', 'logout',
            'register', 'register.store',
            'verification.notice', 'verification.verify', 'verification.send',
            'password.request', 'password.email', 'password.confirm', 'password.update',
        ];
        $current = Route::currentRouteName();
        if ($current && in_array($current, $whitelist, true)) {
            return $next($request);
        }

        // ⑦ 会社＆プロフィール解決
        $uid = $user->id;

        $company = null;
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
            $company = Company::where('user_id', $uid)->orderByDesc('id')->first();
        }
        if (!$company && Schema::hasTable('company_user') && Schema::hasColumn('company_user', 'company_id')) {
            $companyId = DB::table('company_user')
                ->where('user_id', $uid)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('company_id');
            if ($companyId) {
                $company = Company::find($companyId);
            }
        }

        $profile = null;
        if ($company) {
            $profile = CompanyProfile::where('company_id', $company->id)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();
        }
        if (!$profile && Schema::hasColumn('company_profiles', 'user_id')) {
            $profile = CompanyProfile::where('user_id', $uid)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();
        }

        // ⑧ 完了チェック（プロフィール優先、無ければ company の値も一部参照）
        if ($profile && $this->isProfileComplete($profile, $company)) {
            return $next($request);
        }

        // ⑨ 未完了 → 編集画面へ
        return $this->redirectToOnboarding();
    }

    private function redirectToOnboarding()
    {
        if (Route::has('onboarding.company.edit')) {
            return redirect()->route('onboarding.company.edit')
                ->with('status', '企業情報の必須入力が完了するまで、先にこちらをご対応ください。');
        }
        if (Route::has('user.company.edit')) {
            return redirect()->route('user.company.edit')
                ->with('status', '企業情報の必須入力が完了するまで、先にこちらをご対応ください。');
        }
        return redirect('/')->with('status', '企業情報の必須入力が未完了です。');
    }

    /**
     * Bladeの必須（赤＊）と Controller::judgeCompleted() に合わせた完了判定
     * - 必須: description, postal_code, prefecture, city, address1, industry
     * - 会社名(カナ): company_name_kana or kana のどちらか
     * - 従業員数: employees_count or employees のどちらか
     * - プロフィールに列が無い場合、一部は company テーブル値も代替可
     */
    private function isProfileComplete(CompanyProfile $p, ?Company $company = null): bool
    {
        // 早期パス
        if ($p->is_completed === true) return true;
        if (method_exists($p, 'passesCompletionValidation') && $p->passesCompletionValidation()) return true;

        // --- 1) フォーム必須（電話やロゴは必須ではない） ---
        $required = [
            'description',   // 事業内容
            'postal_code',   // 郵便番号
            'prefecture',    // 都道府県
            'city',          // 市区町村
            'address1',      // 番地・建物
            'industry',      // 業種
        ];

        $need = 0;
        $have = 0;

        foreach ($required as $col) {
            $need++;
            $ok = false;

            // 1) プロフィール側に値があればOK
            if (Schema::hasColumn('company_profiles', $col) && filled($p->{$col})) {
                $ok = true;
            }
            // 2) プロフィールに列が無い or 空なら、company テーブルの近似列で代替OK
            elseif ($company) {
                $companyCols = match ($col) {
                    'description' => ['description'],
                    'postal_code' => ['postal_code', 'zip'],
                    'prefecture'  => ['prefecture', 'state'],
                    'city'        => ['city'],
                    'address1'    => ['address1', 'street'],
                    'industry'    => ['industry'],
                    default       => [],
                };
                foreach ($companyCols as $ck) {
                    if (Schema::hasColumn($company->getTable(), $ck) && filled($company->{$ck})) {
                        $ok = true;
                        break;
                    }
                }
            }

            if ($ok) $have++;
        }

        // --- 2) 会社名（カナ）: company_name_kana or kana のどちらか ---
        $need++;
        $kanaOk = false;
        if (Schema::hasColumn('company_profiles', 'company_name_kana') && filled($p->company_name_kana)) {
            $kanaOk = true;
        } elseif (Schema::hasColumn('company_profiles', 'kana') && filled($p->kana)) {
            $kanaOk = true;
        }
        // プロフィールに無い/空 ⇒ company テーブル側の候補も一応見る
        if (!$kanaOk && $company) {
            foreach (['company_name_kana', 'name_kana', 'company_kana', 'kana'] as $ck) {
                if (Schema::hasColumn($company->getTable(), $ck) && filled($company->{$ck})) {
                    $kanaOk = true;
                    break;
                }
            }
        }
        if ($kanaOk) $have++;

        // --- 3) 従業員数: employees_count or employees のどちらか ---
        $need++;
        $empOk = false;
        if (Schema::hasColumn('company_profiles', 'employees_count') && filled($p->employees_count)) {
            $empOk = true;
        } elseif (Schema::hasColumn('company_profiles', 'employees') && filled($p->employees)) {
            $empOk = true;
        }
        if (!$empOk && $company) {
            foreach (['employees_count', 'employees'] as $ck) {
                if (Schema::hasColumn($company->getTable(), $ck) && filled($company->{$ck})) {
                    $empOk = true;
                    break;
                }
            }
        }
        if ($empOk) $have++;

        // --- 完全一致でOK ---
        return $need > 0 ? ($have === $need) : false;
    }
}
