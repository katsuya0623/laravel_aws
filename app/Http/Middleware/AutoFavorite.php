<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Job;

class AutoFavorite
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // auth:web 通過後なのでユーザーは確実に居る
        if ($request->boolean('autofav') && auth()->check()) {
            $routeJob = $request->route('job'); // {job:slug} を想定

            // {job} がモデルでも文字列でも対応
            $job = null;
            if ($routeJob instanceof Job) {
                $job = $routeJob;
            } elseif (is_string($routeJob) || is_numeric($routeJob)) {
                $job = Job::query()
                    ->when(
                        is_numeric($routeJob),
                        fn($q) => $q->where('id', $routeJob),
                        fn($q) => $q->where('slug', $routeJob)
                    )
                    ->first();
            }

            // ユーザーのお気に入りへ追加（既存は保持）
            if ($job) {
                auth()->user()->favorites()->syncWithoutDetaching([$job->id]);
            }
        }

        return $next($request);
    }
}
