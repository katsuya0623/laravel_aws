<?php
// app/Http/Middleware/ShareResolvedRole.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use App\Support\RoleResolver;

class ShareResolvedRole
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // まず既存のロジックで解決
            $role = RoleResolver::resolve($user);

            // ★ 会社の証跡があれば company に補正（“自己修復”）
            if ($role !== 'company') {
                $hasCompanyLink =
                    DB::table('company_user')->where('user_id', $user->id)->exists()
                    || DB::table('companies')->where('user_id', $user->id)->exists()
                    || DB::table('company_profiles')->where('user_id', $user->id)->exists();

                if ($hasCompanyLink) {
                    $role = 'company';
                }
            }

            View::share('role', $role);
        }

        return $next($request);
    }
}
