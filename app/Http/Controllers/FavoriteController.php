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
            ->latest('favorites.created_at')   // 追加順に並ぶよう修正
            ->paginate(12)
            ->withQueryString();

        return view('mypage.favorites.index', compact('favorites'));
    }

    /** 追加（ログイン必須ルート用） */
    public function store(Request $request, Job $job)
    {
        $request->user()->favorites()->syncWithoutDetaching([$job->id]);
        return back()->with('success', 'お気に入りに追加しました。');
    }

    /** 解除（ログイン必須ルート用） */
    public function destroy(Request $request, Job $job)
    {
        $request->user()->favorites()->detach($job->id);
        return back()->with('success', 'お気に入りを解除しました。');
    }

    /** AJAXトグル（ログイン必須ルート用） */
    public function toggle(Request $request, Job $job)
    {
        $user   = $request->user();
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

    /**
     * ★ ゲストも押せる入口：
     * 「お気に入り登録 ⇒（未ログインなら）ログイン/新規登録 ⇒ 自動お気に入り ⇒ 応募画面へ」
     * web.php: POST /recruit_jobs/{slugOrId}/favorite-apply から呼ばれる
     */
    public function favoriteAndApply(Request $request, string $slugOrId)
    {
        // slug でも id でも取得可
        $job = Job::query()
            ->when(
                is_numeric($slugOrId),
                fn($q) => $q->where('id', $slugOrId),
                fn($q) => $q->where('slug', $slugOrId)
            )
            ->firstOrFail();

        // 未ログイン → ログインへ。戻り先は 求人詳細ページ ?autofav=1
        if (!auth()->check()) {
            $redirectTo = route('front.jobs.show', [
                'slugOrId' => $job->slug, // ★ここを job → slugOrId に修正
                'autofav'  => 1,
            ]);

            return redirect()->route('login.intended', [
                'redirect' => $redirectTo,
            ]);
        }

        // ログイン済み → 即お気に入り登録
        $request->user()->favorites()->syncWithoutDetaching([$job->id]);

        // 求人詳細に戻る
        return redirect()->route('front.jobs.show', [
            'slugOrId' => $job->slug, // ★ここも job → slugOrId
        ]);
    }
}
