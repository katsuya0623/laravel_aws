<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\CompanyProfile;

/**
 * 企業ユーザーで、企業情報が未完了の間は
 * 強制的にオンボーディング（初回入力/編集）へ誘導する。
 *
 * 適用対象：
 * - ログイン済み かつ 「company」ロールのユーザーのみ
 *
 * 判定：
 * - CompanyProfile::is_completed === true
 *   もしくは
 * - CompanyProfile::passesCompletionValidation() が true
 */
class EnsureCompanyProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        // 未ログインは対象外
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // ===== 企業ユーザー以外は対象外（企業だけに適用） =====
        $isCompany = false;

        // 1) hasRole('company') を持っていればそれで判定
        if (method_exists($user, 'hasRole')) {
            $isCompany = $user->hasRole('company');
        }

        // 2) RoleResolver があればそれでも判定（fallback）
        if (!$isCompany && class_exists(\App\Support\RoleResolver::class)) {
            try {
                $role = \App\Support\RoleResolver::resolve($user);
                $isCompany = ($role === 'company');
            } catch (\Throwable $e) {
                // だまって続行
            }
        }

        if (!$isCompany) {
            return $next($request); // ← 企業以外は素通し
        }

        // ===== 静的アセット類は除外 =====
        $path = ltrim($request->path(), '/');
        if (preg_match('#^(storage|assets|build|vendor|images|img|css|js)/#', $path)) {
            return $next($request);
        }

        // ===== 例外ルート（オンボーディング/編集/認証系）は除外 =====
        $whitelist = [
            // あなたの実ルート名
            'onboarding.company.edit',
            'onboarding.company.update',
            'user.company.edit',
            'user.company.update',

            // 将来 first 画面を分けた場合のための保険（無ければ無視されるだけ）
            'company.profile.first',
            'company.profile.first.store',

            // 認証・検証周辺
            'login',
            'logout',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'password.request',
            'password.email',
            'password.confirm',
            'password.update',
        ];
        $current = Route::currentRouteName();
        if ($current && in_array($current, $whitelist, true)) {
            return $next($request);
        }

        // ===== プロフィールの特定（代表 user_id → pivot users の順） =====
        $profile = CompanyProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            $profile = CompanyProfile::whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })->first();
        }

        // プロフィールが無い（未招待/未作成）場合は通す
        if (!$profile) {
            return $next($request);
        }

        // ===== 完了判定 =====
        $completed =
            ($profile->is_completed === true) ||
            (method_exists($profile, 'passesCompletionValidation') && $profile->passesCompletionValidation());

        if ($completed) {
            return $next($request);
        }

        // ===== リダイレクト先の決定（存在チェックして優先度順に） =====
        if (Route::has('onboarding.company.edit')) {
            return redirect()->route('onboarding.company.edit')
                ->with('status', '企業情報の必須入力が完了するまで、先にこちらをご対応ください。');
        }
        if (Route::has('user.company.edit')) {
            return redirect()->route('user.company.edit')
                ->with('status', '企業情報の必須入力が完了するまで、先にこちらをご対応ください。');
        }
        if (Route::has('company.profile.first')) {
            return redirect()->route('company.profile.first')
                ->with('status', '企業情報の必須入力が完了するまで、先にこちらをご対応ください。');
        }
        if (Route::has('company.profile.edit')) {
            return redirect()->route('company.profile.edit')
                ->with('status', '企業情報の必須入力が完了するまで、先にこちらをご対応ください。');
        }

        // 最終フォールバック
        return redirect('/')->with('status', '企業情報の必須入力が未完了です。');
    }
}
