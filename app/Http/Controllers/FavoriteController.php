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
            ->when(is_numeric($slugOrId), fn ($q) => $q->where('id', $slugOrId),
                                 fn ($q) => $q->where('slug', $slugOrId))
            ->firstOrFail();

        // 未ログイン → 認証へ。戻り先は 応募ゲート?autofav=1
        if (!auth()->check()) {
            $redirectTo = route('front.jobs.apply.gate', ['job' => $job->slug, 'autofav' => 1]);
            return redirect()->route('login.intended', ['redirect' => $redirectTo]);
            // 新規登録優先にしたければ ↓ に差し替え
            // return redirect()->route('register.intended', ['redirect' => $redirectTo]);
        }

        // ログイン済み → 即付与して応募ゲートへ
        $request->user()->favorites()->syncWithoutDetaching([$job->id]);

        return redirect()->route('front.jobs.apply.gate', ['job' => $job->slug]);
    }
}
