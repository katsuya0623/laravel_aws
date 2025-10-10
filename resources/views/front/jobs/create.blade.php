<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Job; // ← モデル名が RecruitJob 等ならここを合わせてください
use App\Support\RoleResolver;

class JobController extends Controller
{
    /** 求人一覧（既存の仕様に合わせて最低限） */
    public function index(Request $request)
    {
        $q      = trim($request->input('q', ''));
        $status = $request->input('status', '');

        $jobs = Job::query()
            ->when($q !== '', function ($qbuilder) use ($q) {
                // スペース区切り AND 検索
                foreach (preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) as $kw) {
                    $qbuilder->where(function ($qq) use ($kw) {
                        $like = "%{$kw}%";
                        $qq->where('title', 'like', $like)
                           ->orWhere('description', 'like', $like);
                    });
                }
            })
            ->when(in_array($status, ['draft','published'], true), fn ($qb) => $qb->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('front.jobs.index', compact('jobs', 'q', 'status'));
    }

    /** 求人詳細（slug or id） */
    public function show(string $slugOrId)
    {
        $job = Job::query()
            ->when(is_numeric($slugOrId), fn ($q) => $q->where('id', $slugOrId),
                   fn ($q) => $q->where('slug', $slugOrId))
            ->firstOrFail();

        return view('front.jobs.show', compact('job'));
    }

    /** 求人作成フォーム（企業ユーザー専用） */
    public function create()
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403, '権限がありません。');
        }

        // front/jobs/create.blade.php が無ければ jobs/create.blade.php を使う
        $view = view()->exists('front.jobs.create') ? 'front.jobs.create' : (view()->exists('jobs.create') ? 'jobs.create' : null);
        if (!$view) {
            abort(500, '求人作成ビューが見つかりません。（front/jobs/create.blade.php か jobs/create.blade.php を作成してください）');
        }

        return view($view);
    }

    /** 求人登録処理（企業ユーザー専用） */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403, '権限がありません。');
        }

        $data = $request->validate([
            'title'       => ['required','string','max:255'],
            'description' => ['required','string'],
        ]);

        // slug がある運用想定。無いテーブルでも NULL 可 or カラムを外してOK
        $slugBase = Str::slug($data['title']);
        $slug     = $slugBase !== '' ? $slugBase.'-'.Str::lower(Str::random(6)) : Str::lower(Str::random(12));

        $job = Job::create([
            'title'       => $data['title'],
            'description' => $data['description'],
            'status'      => 'draft',
            'slug'        => $slug,
            'user_id'     => $user->id, // カラムが無ければ消してください
        ]);

        return redirect()->route('front.jobs.index')->with('success', '求人を作成しました。');
    }

    /** （任意）編集フォーム */
    public function edit(Job $job)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403);
        }
        return view()->exists('front.jobs.edit')
            ? view('front.jobs.edit', compact('job'))
            : view('jobs.edit', compact('job'));
    }

    /** （任意）更新 */
    public function update(Request $request, Job $job)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403);
        }

        $data = $request->validate([
            'title'       => ['required','string','max:255'],
            'description' => ['required','string'],
            'status'      => ['nullable','in:draft,published'],
        ]);

        $job->fill($data);
        $job->save();

        return redirect()->route('front.jobs.index')->with('success', '求人を更新しました。');
    }

    /** （任意）削除 */
    public function destroy(Job $job)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403);
        }
        $job->delete();
        return redirect()->route('front.jobs.index')->with('success', '求人を削除しました。');
    }
}
