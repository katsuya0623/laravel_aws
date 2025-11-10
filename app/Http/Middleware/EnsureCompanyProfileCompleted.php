<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Company;
use App\Models\CompanyProfile;

class EnsureCompanyProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = optional($request->route())->getName();

        Log::info('[EPC] START', [
            'method' => $request->method(),
            'path'   => $request->path(),
            'route'  => $routeName,
            'uid'    => Auth::id(),
        ]);

        // ゲストは対象外
        if (!Auth::check()) {
            Log::info('[EPC] bypass: guest');
            return $next($request);
        }
        $user = Auth::user();

        // 認証フローは邪魔しない
        if (is_null($user->email_verified_at)) {
            Log::info('[EPC] bypass: email not verified yet');
            return $next($request);
        }

        // エンドユーザー / 管理者は対象外
        if (($user->role ?? null) === 'enduser' || ($user->role ?? null) === null) {
            Log::info('[EPC] bypass: enduser or no role', ['role' => $user->role ?? null]);
            return $next($request);
        }
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            Log::info('[EPC] bypass: admin');
            return $next($request);
        }

        // 企業ユーザー判定
        $isCompany = false;
        if (method_exists($user, 'hasRole')) $isCompany = $user->hasRole('company');
        if (!$isCompany && class_exists(\App\Support\RoleResolver::class)) {
            try { $isCompany = (\App\Support\RoleResolver::resolve($user) === 'company'); }
            catch (\Throwable $e) { Log::warning('[EPC] RoleResolver error', ['e' => $e->getMessage()]); }
        }
        if (!$isCompany) {
            Log::info('[EPC] bypass: not company user');
            return $next($request);
        }

        // 静的アセット除外
        $path = ltrim($request->path(), '/');
        if (preg_match('#^(storage|assets|build|vendor|images|img|css|js)/#', $path)) {
            Log::info('[EPC] bypass: static asset', ['path' => $path]);
            return $next($request);
        }

        // JSON/AJAX は強制リダイレクトしない
        if ($request->expectsJson() || $request->ajax()) {
            Log::info('[EPC] bypass: ajax/json');
            return $next($request);
        }

        // 例外ルート
        $whitelistPatterns = [
            'user.company.*',
            'onboarding.company.*',
            'company.profile.first*',
            'login', 'logout',
            'register', 'register.*',
            'verification.*',
            'password.*',
            'storage.*',
            'invites.*',
            'filament.*',
        ];
        if ($routeName) {
            foreach ($whitelistPatterns as $pat) {
                if (Str::is($pat, $routeName)) {
                    Log::info('[EPC] bypass: whitelist route', ['pattern' => $pat, 'route' => $routeName]);
                    return $next($request);
                }
            }
        }

        // 保存系は必ず通す（ルート名 or パス前方一致）
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            $saveRouteOk =
                ($routeName && (Str::is('user.company.*', $routeName)
                             || Str::is('onboarding.company.*', $routeName)
                             || Str::is('company.profile.first*', $routeName)
                             || Str::is('invites.*', $routeName)))
                || Str::startsWith($path, ['user/company', 'onboarding/company']);
            if ($saveRouteOk) {
                Log::info('[EPC] bypass: save request allowed', [
                    'method' => $request->method(), 'route' => $routeName, 'path' => $path,
                ]);
                return $next($request);
            }
        }

        // GET 編集ページもパス前方一致で許可
        if (Str::startsWith($path, 'user/company') || Str::startsWith($path, 'onboarding/company')) {
            Log::info('[EPC] bypass: edit page path allow', ['path' => $path]);
            return $next($request);
        }

        // ===== 判定ロジック =====
        $uid = $user->id;

        // user_idに completed が1つでもあれば通過
        $anyCompleted = false;
        if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles','is_completed')) {
            $anyCompleted = DB::table('company_profiles')
                ->where('user_id', $uid)->where('is_completed', 1)->exists();
        }
        Log::info('[EPC] anyCompleted?', ['anyCompleted' => $anyCompleted]);
        if ($anyCompleted) {
            Log::info('[EPC] PASS: user has a completed profile');
            return $next($request);
        }

        // 会社ID解決：セッション → pivot → profile → companies
        $companyId = (int) session('__company_lock_id');
        Log::info('[EPC] company lock (session)', ['company_lock_id' => $companyId]);

        if (!$companyId && Schema::hasTable('company_user') && Schema::hasColumn('company_user','company_id')) {
            $companyId = (int) DB::table('company_user')->where('user_id', $uid)->value('company_id');
            Log::info('[EPC] company from pivot', ['company_id' => $companyId]);
        }
        if (!$companyId && Schema::hasTable('company_profiles')) {
            $companyId = (int) DB::table('company_profiles')
                ->where('user_id', $uid)->orderByDesc('is_completed')->orderByDesc('id')->value('company_id');
            Log::info('[EPC] company from profiles', ['company_id' => $companyId]);
        }
        if (!$companyId && Schema::hasTable('companies') && Schema::hasColumn('companies','user_id')) {
            $candidate = DB::table('companies as c')
                ->leftJoin('company_profiles as p', 'p.company_id', '=', 'c.id')
                ->where('c.user_id', $uid)
                ->orderByRaw('COALESCE(p.is_completed,0) DESC')
                ->orderByDesc('p.id')->orderByDesc('c.id')->value('c.id');
            $companyId = (int) $candidate;
            Log::info('[EPC] company from companies', ['company_id' => $companyId]);
        }
        if (!$companyId) {
            Log::info('[EPC] REDIRECT: no company resolved');
            return $this->redirectToOnboarding();
        }

        // 最新プロフィール & 会社
        $profile = Schema::hasTable('company_profiles')
            ? CompanyProfile::where('company_id', $companyId)->latest('id')->first()
            : null;
        $company = Schema::hasTable('companies') ? Company::find($companyId) : null;

        // フラグ
        $isCompletedFlag = ($profile && Schema::hasColumn('company_profiles','is_completed'))
            ? (int) ($profile->is_completed ?? 0) : 0;
        Log::info('[EPC] latest profile flag', ['company_id'=>$companyId, 'is_completed'=>$isCompletedFlag]);

        // 実データ判定（緩和版）
        // 7項目：company_name / postal_code / prefecture / city / address1 / industry / description
        $checkMap = [
            'company_name' => ['p'=>'company_name', 'c'=>['company_name','name']],
            'postal_code'  => ['p'=>'postal_code',  'c'=>['postal_code','zip']],
            'prefecture'   => ['p'=>'prefecture',   'c'=>['prefecture','state']],
            'city'         => ['p'=>'city',         'c'=>['city']],
            'address1'     => ['p'=>'address1',     'c'=>['address1','street']],
            'industry'     => ['p'=>'industry',     'c'=>['industry']],
            'description'  => ['p'=>'description',  'c'=>['description']],
        ];

        $filled = [];
        $missing = [];
        $presentCount = 0;

        foreach ($checkMap as $key => $cols) {
            $ok = false;

            // profile 側
            if ($profile && isset($cols['p']) && Schema::hasColumn('company_profiles', $cols['p'])) {
                $val = $profile->{$cols['p']} ?? null;
                if (filled($val)) $ok = true;
            }
            // company 側
            if (!$ok && $company) {
                foreach ($cols['c'] as $ck) {
                    if (Schema::hasColumn($company->getTable(), $ck) && filled($company->{$ck})) { $ok = true; break; }
                }
            }

            if ($ok) { $presentCount++; $filled[] = $key; }
            else     { $missing[] = $key; }
        }

        Log::info('[EPC] data completeness', [
            'company_id'    => $companyId,
            'present_count' => $presentCount,
            'filled'        => $filled,
            'missing'       => $missing,
        ]);

        // しきい値：7項目中 5 以上
        $completeByData = ($presentCount >= 5);

        if ($isCompletedFlag === 1 || $completeByData) {
            if ($completeByData && $profile && Schema::hasColumn('company_profiles','is_completed') && (int)$profile->is_completed !== 1) {
                Log::info('[EPC] auto-fix: set is_completed=1', ['company_id'=>$companyId,'filled'=>$filled]);
                try { $profile->is_completed = 1; $profile->saveQuietly(); }
                catch (\Throwable $e) { Log::warning('[EPC] auto-fix save failed', ['e'=>$e->getMessage()]); }
            }
            Log::info('[EPC] PASS: completed checks OK');
            return $next($request);
        }

        Log::info('[EPC] REDIRECT: profile not completed by data');
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

    /** 参考：厳格版（保持用） */
    private function isProfileComplete(CompanyProfile $p, ?Company $company = null): bool
    {
        if (Schema::hasColumn('company_profiles','is_completed')) {
            $flag = $p->is_completed;
            if ($flag === true || $flag === 1 || $flag === '1') return true;
        }

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
                ($company && collect($maps['company'])->contains(fn($c) => Schema::hasColumn($company->getTable(), $c)));
            if (!$existsSomewhere) continue;

            $need++;
            $ok = false;
            if (Schema::hasColumn('company_profiles', $maps['company_profiles']) && filled($p->{$maps['company_profiles']})) $ok = true;
            elseif ($company) {
                foreach ($maps['company'] as $ck) if (Schema::hasColumn($company->getTable(), $ck) && filled($company->{$ck})) { $ok = true; break; }
            }
            if ($ok) $have++;
        }

        // 会社名カナ
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
            $need++; $kanaOk = false;
            foreach ($kanaColsProfile as $col) if (filled($p->{$col})) { $kanaOk = true; break; }
            if (!$kanaOk && $company) foreach ($kanaColsCompany as $col) if (filled($company->{$col})) { $kanaOk = true; break; }
            if ($kanaOk) $have++;
        }

        // 従業員数
        $empCols = array_filter([
            Schema::hasColumn('company_profiles','employees_count') ? 'employees_count' : null,
            Schema::hasColumn('company_profiles','employees') ? 'employees' : null,
        ]);
        $empColsCompany = $company ? array_values(array_filter([
            Schema::hasColumn($company->getTable(),'employees_count') ? 'employees_count' : null,
            Schema::hasColumn($company->getTable(),'employees') ? 'employees' : null,
        ])) : [];
        if (!empty($empCols) || !empty($empColsCompany)) {
            $need++; $empOk = false;
            foreach ($empCols as $col) if (filled($p->{$col})) { $empOk = true; break; }
            if (!$empOk && $company) foreach ($empColsCompany as $col) if (filled($company->{$col})) { $empOk = true; break; }
            if ($empOk) $have++;
        }

        return $need > 0 && $have === $need;
    }
}
