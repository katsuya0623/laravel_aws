<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;

class FavoriteController extends Controller
{
    /** マイページ：お気に入り一覧 */
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()
            ->with(['company'])
            ->withCount('favoredBy')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('mypage.favorites.index', compact('favorites'));
    }

    /** 追加 */
    public function store(Request $request, Job $job)
    {
        $request->user()->favorites()->syncWithoutDetaching([$job->id]);
        return back()->with('success', 'お気に入りに追加しました。');
    }

    /** 解除 */
    public function destroy(Request $request, Job $job)
    {
        $request->user()->favorites()->detach($job->id);
        return back()->with('success', 'お気に入りを解除しました。');
    }

    /** AJAXトグル */
    public function toggle(Request $request, Job $job)
    {
        $user = $request->user();
        $exists = $user->favorites()->where('recruit_jobs.id', $job->id)->exists();

        if ($exists) {
            $user->favorites()->detach($job->id);
        } else {
            $user->favorites()->syncWithoutDetaching([$job->id]);
        }

        return response()->json([
            'favorited' => ! $exists,
            'count'     => $job->favoredBy()->count(),
            'job_id'    => $job->id,
            'message'   => $exists ? 'お気に入りを解除しました。' : 'お気に入りに追加しました。',
        ]);
    }
}
