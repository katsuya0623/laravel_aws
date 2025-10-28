<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\CompanyProfile;

class EnsureCompanyProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        // ================================
        // ① 未ログインならスルー
        // ================================
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // ================================
        // ② メール未認証ユーザーはスルー（←追加）
        // ================================
        if (is_null($user->email_verified_at)) {
            // 認証フロー中（verification.noticeなど）をブロックしない
            return $next($request);
        }

        // ================================
        // ③ エンドユーザー／管理者は完全スルー
        // ================================
        if (($user->role ?? null) === 'enduser' || ($user->role ?? null) === null) {
            return $next($request);
        }
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        // ================================
        // ④ 企業ユーザー判定
        // ================================
        $isCompany = false;

        // hasRole() が存在すれば優先
        if (method_exists($user, 'hasRole')) {
            $isCompany = $user->hasRole('company');
        }

        // RoleResolver 経由の判定（例外は無視）
        if (!$isCompany && class_exists(\App\Support\RoleResolver::class)) {
            try {
                $isCompany = (\App\Support\RoleResolver::resolve($user) === 'company');
            } catch (\Throwable $e) {}
        }

        // 企業ユーザー以外はスルー
        if (!$isCompany) {
            return $next($request);
        }

        // ================================
        // ⑤ 静的アセット除外
        // ================================
        $path = ltrim($request->path(), '/');
        if (preg_match('#^(storage|assets|build|vendor|images|img|css|js)/#', $path)) {
            return $next($request);
        }

        // ================================
        // ⑥ ホワイトリスト除外
        // ================================
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

        // ================================
        // ⑦ 会社IDの解決
        // ================================
        $uid = $user->id;
        $companyId = null;

        $companyId = CompanyProfile::where('user_id', $uid)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->value('company_id');

        if (!$companyId && Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
            $companyId = DB::table('companies')->where('user_id', $uid)->value('id');
        }

        if (!$companyId && Schema::hasTable('company_user')) {
            $profileId = DB::table('company_user')
                ->where('user_id', $uid)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('company_profile_id');
            if ($profileId) {
                $companyId = DB::table('company_profiles')->where('id', $profileId)->value('company_id');
            }
        }

        // ================================
        // ⑧ プロフィール完了チェック
        // ================================
        if ($companyId) {
            $p = CompanyProfile::where('company_id', $companyId)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            if ($p && $this->isProfileComplete($p)) {
                return $next($request);
            }
        }

        $pByUser = CompanyProfile::where('user_id', $uid)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($pByUser && $this->isProfileComplete($pByUser)) {
            return $next($request);
        }

        // ================================
        // ⑨ 未完了 → 編集画面へ強制遷移
        // ================================
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

    private function isProfileComplete(CompanyProfile $p): bool
    {
        if ($p->is_completed === true) return true;
        if (method_exists($p, 'passesCompletionValidation') && $p->passesCompletionValidation()) return true;

        return filled($p->company_name_kana)
            && filled($p->description)
            && filled($p->postal_code)
            && filled($p->prefecture)
            && filled($p->city)
            && filled($p->address1)
            && filled($p->industry)
            && !is_null($p->employees);
    }
}
