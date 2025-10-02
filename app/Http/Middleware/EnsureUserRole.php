<?php

namespace App\Http\Middleware;

use App\Support\RoleResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * 使い方：->middleware('role:enduser,company') など
     * 可変長 ...$roles で複数ロール対応
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user(); // 現在のガード (auth:web / auth:admin) が優先される
        if (!$user) {
            abort(401);
        }

        $current = RoleResolver::resolve($user);

        // ★ 厳格モード：admin でも無条件には通さない
        //    -> admin を通したいルートでは 'role:admin' と明示する

        if (!in_array($current, $roles, true)) {
            abort(403, 'This area is not available for your role.');
        }

        return $next($request);
    }
}
