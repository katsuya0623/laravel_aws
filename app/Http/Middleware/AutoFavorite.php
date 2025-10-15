<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Job;

class AutoFavorite
{
    public function handle(Request $request, Closure $next)
    {
        // 先に実行（auth:web 通過後なのでユーザーは確実に居る）
        if ($request->boolean('autofav') && auth()->check()) {
            $routeJob = $request->route('job'); // {job:slug}

            // {job} がモデルでも文字列でも対応
            $job = null;
            if ($routeJob instanceof Job) {
                $job = $routeJob;
            } elseif (is_string($routeJob) || is_numeric($routeJob)) {
                $job = Job::query()
                    ->when(is_numeric($routeJob),
                        fn($q) => $q->where('id', $routeJob),
                        fn($q) => $q->where('slug', $routeJob)
                    )->first();
            }

            if ($job) {
                auth()->user()->favorites()->syncWithoutDetaching([$job->id]);
            }
        }

        return $next($request);
    }
}
