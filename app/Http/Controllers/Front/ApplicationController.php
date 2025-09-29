<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Application;
use App\Models\Job;

class ApplicationController extends Controller
{
    /**
     * 応募履歴一覧（自分の分だけ）
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $query = Application::query()->latest('id');

        // applications に user_id があれば user_id で、無ければ email で本人絞り込み
        if (Schema::hasColumn((new Application)->getTable(), 'user_id')) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('email', $user->email);
        }

        // 求人情報を一緒に取得（列がある環境のみ）
        if (Schema::hasColumn((new Application)->getTable(), 'job_id')) {
            // company 関連が定義されていれば合わせて取得（未定義なら無視されます）
            $query->with(['job', 'job.company']);
        }

        $apps = $query->paginate(10);

        // ステータス表示ラベル
        $statusLabels = [
            'applied'   => '応募済み',
            'reviewing' => '書類選考中',
            'interview' => '面接中',
            'offer'     => '内定',
            'rejected'  => 'お見送り',
            'pending'   => '保留', // 画面に出ていた pending も一応ケア
        ];

        // ★ ここを front → mypage に変更
        return view('mypage.applications.index', compact('apps', 'statusLabels'));
    }

    /**
     * 応募詳細（応募の状況を表示）
     */
    public function show(Request $request, Application $application)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // 所有チェック：user_id が無い場合は email で本人確認
        $owns = Schema::hasColumn((new Application)->getTable(), 'user_id')
            ? ($application->user_id === $user->id)
            : ($application->email === $user->email);

        abort_unless($owns, 404);

        // 求人情報を読み込み（job_id 列がある場合のみ）
        if (Schema::hasColumn((new Application)->getTable(), 'job_id')) {
            $application->load(['job', 'job.company']);
        }

        // ステータス表示ラベル
        $statusLabels = [
            'applied'   => '応募済み',
            'reviewing' => '書類選考中',
            'interview' => '面接中',
            'offer'     => '内定',
            'rejected'  => 'お見送り',
            'pending'   => '保留',
        ];

        // ★ ここも front → mypage に変更
        return view('mypage.applications.show', [
            'app'          => $application,
            'statusLabels' => $statusLabels,
        ]);
    }

    /**
     * 求人詳細からの応募（/jobs/{job:slug}/apply）
     */
    public function store(Request $request, Job $job)
    {
        if (!$job || !$job->id) {
            abort(404, 'Job not found');
        }

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:50'],
            'email'   => ['required', 'email'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string'],
        ]);

        $data['job_id'] = $job->id;

        // ログイン済みかつ applications に user_id がある場合は保存
        if ($request->user() && Schema::hasColumn((new Application)->getTable(), 'user_id')) {
            $data['user_id'] = $request->user()->id;
        }

        // ステータス列がある場合の初期値
        if (Schema::hasColumn((new Application)->getTable(), 'status') && empty($data['status'])) {
            $data['status'] = 'applied';
        }

        Application::create($data);

        return redirect()
            ->route('mypage.applications.index')
            ->with('status', '応募が送信されました。担当者からご連絡いたします。');
    }
}
