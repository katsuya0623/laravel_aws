<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /** /recruit_jobs */
    public function index(Request $request)
    {
        $jobs = Job::query()
            ->with(['company'])          // 会社名などを一緒に
            ->withCount('favoredBy')     // ★数 → favored_by_count
            ->published()                // 公開スコープ（カラムがあれば有効）
            ->latest('id')
            ->paginate(12);

        // Bladeが jobs / recruit_jobs のどちらでも動くよう両方で渡す
        return view('front.jobs.index', [
            'jobs'         => $jobs,
            'recruit_jobs' => $jobs,
        ]);
    }

    /** /recruit_jobs/{slugOrId} */
    public function show($slugOrId)
    {
        $job = Job::query()
            ->with(['company'])          // 会社情報
            ->withCount('favoredBy')     // ★数 → favored_by_count
            ->published()                // 必要なら公開のみ
            ->when(
                is_numeric($slugOrId),
                fn ($q) => $q->where('id', (int) $slugOrId),
                fn ($q) => $q->where('slug', $slugOrId)
            )
            ->firstOrFail();

        return view('front.jobs.show', compact('job'));
    }
}
