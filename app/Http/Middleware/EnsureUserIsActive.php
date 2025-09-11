<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && property_exists($user, 'is_active') && ! $user->is_active) {
            auth()->logout();
            abort(403, 'This account is disabled.');
        }
        return $next($request);
    }
}
