<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (!$user) abort(401);

        $current = (string)($user->role ?? 'enduser');
        if (!in_array($current, $roles, true)) {
            abort(403, 'This area is not available for your role.');
        }
        return $next($request);
    }
}
