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
        if (!Auth::check()) return $next($request);
        $user = Auth::user();

        // 認証フローの邪魔をしない
        if (is_null($user->email_verified_at)) return $next($request);

        // エンドユーザー / 管理者は対象外
        if (($user->role ?? null) === 'enduser' || ($user->role ?? null) === null) return $next($request);
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) return $next($request);

        // 企業ユーザー判定
        $isCompany = false;
        if (method_exists($user, 'hasRole')) $isCompany = $user->hasRole('company');
        if (!$isCompany && class_exists(\App\Support\RoleResolver::class)) {
            try { $isCompany = (\App\Support\RoleResolver::resolve($user) === 'company'); } catch (\Throwable $e) {}
        }
        if (!$isCompany) return $next($request);

        // 静的アセットは除外
        $path = ltrim($request->path(), '/');
        if (preg_match('#^(storage|assets|build|vendor|images|img|css|js)/#', $path)) return $next($request);

        // 例外ルート（オンボ & 編集系は常に通す）
        $whitelist = [
            'onboarding.company.edit','onboarding.company.update',
            'user.company.edit','user.company.update',
            'company.profile.first','company.profile.first.store',
            'login','logout','register','register.store',
            'verification.notice','verification.verify','verification.send',
            'password.request','password.email','password.confirm','password.update',
        ];
        $current = Route::currentRouteName();
        if ($current && in_array($current, $whitelist, true)) return $next($request);

        // 会社解決（user_id 優先 + pivot 保険）
        $uid = $user->id;
        $company = null;
        if (Schema::hasTable('companies') && Schema::hasColumn('companies','user_id')) {
            $company = Company::where('user_id',$uid)->latest('id')->first();
        }
        if (!$company && Schema::hasTable('company_user') && Schema::hasColumn('company_user','company_id')) {
            $cid = DB::table('company_user')->where('user_id',$uid)->latest('id')->value('company_id');
            if ($cid) $company = Company::find($cid);
        }

        // プロフィールは company_id で取る（user_id は保険）
        $profile = null;
        if ($company) {
            $profile = CompanyProfile::where('company_id',$company->id)->latest('id')->first();
        }
        if (!$profile && Schema::hasColumn('company_profiles','user_id')) {
            $profile = CompanyProfile::where('user_id',$uid)->latest('id')->first();
        }

        // 完了判定（通過時にフラグを自動修復）
        if ($profile && $this->isProfileComplete($profile, $company)) {

            // is_completed 列があり、未立てなら 1 を立てる（UX優先で try/catch）
            if (Schema::hasColumn('company_profiles','is_completed')) {
                $flag = $profile->is_completed;
                if (!in_array($flag, [true, 1, '1'], true)) {
                    $profile->is_completed = 1;
                    try { $profile->saveQuietly(); } catch (\Throwable $e) {}
                }
            }

            return $next($request);
        }

        // 未完了 → オンボーディングへ
        return $this->redirectToOnboarding();
    }

    private function redirectToOnboarding()
    {
        if (Route::has('onboarding.company.edit')) {
            return redirect()->route('onboarding.company.edit')
                ->with('status','企業情報の必須入力が完了するまで、先にこちらをご対応ください。');
        }
        if (Route::has('user.company.edit')) {
            return redirect()->route('user.company.edit')
                ->with('status','企業情報の必須入力が完了するまで、先にこちらをご対応ください。');
        }
        return redirect('/')->with('status','企業情報の必須入力が未完了です。');
    }

    /** Bladeの必須と整合する堅牢な完了判定 */
    private function isProfileComplete(CompanyProfile $p, ?Company $company = null): bool
    {
        // 1) is_completed 優先（int/str/bool すべて 1 扱い）
        if (Schema::hasColumn('company_profiles','is_completed')) {
            $flag = $p->is_completed;
            if ($flag === true || $flag === 1 || $flag === '1') return true;
        }

        // 2) 実データで判定（「その列がどこにも存在しない」ものは必須にカウントしない）
        $required = [
            'description' => ['company_profiles'=>'description', 'company'=>['description']],
            'postal_code' => ['company_profiles'=>'postal_code', 'company'=>['postal_code','zip']],
            'prefecture'  => ['company_profiles'=>'prefecture',  'company'=>['prefecture','state']],
            'city'        => ['company_profiles'=>'city',        'company'=>['city']],
            'address1'    => ['company_profiles'=>'address1',    'company'=>['address1','street']],
            'industry'    => ['company_profiles'=>'industry',    'company'=>['industry']],
        ];

        $need = 0; $have = 0;

        foreach ($required as $maps) {
            $existsSomewhere =
                Schema::hasColumn('company_profiles', $maps['company_profiles']) ||
                ($company && collect($maps['company'])->contains(function ($c) use ($company) {
                    return Schema::hasColumn($company->getTable(), $c);
                }));

            if (!$existsSomewhere) {
                // その必須は “テーブルに物理列が無い環境” → 判定対象にしない
                continue;
            }

            $need++;
            $ok = false;

            if (Schema::hasColumn('company_profiles', $maps['company_profiles']) && filled($p->{$maps['company_profiles']})) {
                $ok = true;
            } elseif ($company) {
                foreach ($maps['company'] as $ck) {
                    if (Schema::hasColumn($company->getTable(), $ck) && filled($company->{$ck})) { $ok = true; break; }
                }
            }

            if ($ok) $have++;
        }

        // 会社名カナ：存在するどれかを満たせば “1要件” として数える
        $kanaColsProfile = array_filter([
            Schema::hasColumn('company_profiles','company_name_kana') ? 'company_name_kana' : null,
            Schema::hasColumn('company_profiles','kana') ? 'kana' : null,
        ]);
        $kanaColsCompany = $company ? array_values(array_filter([
            Schema::hasColumn($company->getTable(),'company_name_kana') ? 'company_name_kana' : null,
            Schema::hasColumn($company->getTable(),'name_kana') ? 'name_kana' : null,
            Schema::hasColumn($company->getTable(),'company_kana') ? 'company_kana' : null,
            Schema::hasColumn($company->getTable(),'kana') ? 'kana' : null,
        ])) : [];

        if (!empty($kanaColsProfile) || !empty($kanaColsCompany)) {
            $need++;
            $kanaOk = false;
            foreach ($kanaColsProfile as $col) if (filled($p->{$col})) { $kanaOk = true; break; }
            if (!$kanaOk && $company) foreach ($kanaColsCompany as $col) if (filled($company->{$col})) { $kanaOk = true; break; }
            if ($kanaOk) $have++;
        }

        // 従業員数（employees_count / employees のどちらか、存在する場合のみカウント）
        $empCols = array_filter([
            Schema::hasColumn('company_profiles','employees_count') ? 'employees_count' : null,
            Schema::hasColumn('company_profiles','employees') ? 'employees' : null,
        ]);
        $empColsCompany = $company ? array_values(array_filter([
            Schema::hasColumn($company->getTable(),'employees_count') ? 'employees_count' : null,
            Schema::hasColumn($company->getTable(),'employees') ? 'employees' : null,
        ])) : [];

        if (!empty($empCols) || !empty($empColsCompany)) {
            $need++;
            $empOk = false;
            foreach ($empCols as $col) if (filled($p->{$col})) { $empOk = true; break; }
            if (!$empOk && $company) foreach ($empColsCompany as $col) if (filled($company->{$col})) { $empOk = true; break; }
            if ($empOk) $have++;
        }

        return $need > 0 && $have === $need;
    }
}
