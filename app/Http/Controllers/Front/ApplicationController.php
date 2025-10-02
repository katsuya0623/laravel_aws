<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        if (!$user) return redirect()->route('login');

        $query = Application::query()->latest('id');

        // applications に user_id があれば user_id、無ければ email で本人絞り込み
        if (Schema::hasColumn((new Application)->getTable(), 'user_id')) {
            $query->where('user_id', $user->id);
        } else {
            if (Schema::hasColumn((new Application)->getTable(), 'email')) {
                $query->where('email', $user->email);
            }
        }

        // 求人情報を一緒に取得（列がある環境のみ）
        if (Schema::hasColumn((new Application)->getTable(), 'job_id')) {
            $query->with(['job', 'job.company']);
        }

        $apps = $query->paginate(10);

        $statusLabels = [
            'applied'   => '応募済み',
            'reviewing' => '書類選考中',
            'interview' => '面接中',
            'offer'     => '内定',
            'rejected'  => 'お見送り',
            'pending'   => '保留',
        ];

        return view('mypage.applications.index', compact('apps', 'statusLabels'));
    }

    /**
     * 応募詳細（応募の状況を表示）
     */
    public function show(Request $request, Application $application)
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        // 所有チェック：user_id が無い場合は email で本人確認
        $owns = Schema::hasColumn((new Application)->getTable(), 'user_id')
            ? ($application->user_id === $user->id)
            : ($application->email === $user->email);

        abort_unless($owns, 404);

        if (Schema::hasColumn((new Application)->getTable(), 'job_id')) {
            $application->load(['job', 'job.company']);
        }

        $statusLabels = [
            'applied'   => '応募済み',
            'reviewing' => '書類選考中',
            'interview' => '面接中',
            'offer'     => '内定',
            'rejected'  => 'お見送り',
            'pending'   => '保留',
        ];

        return view('mypage.applications.show', [
            'app'          => $application,
            'statusLabels' => $statusLabels,
        ]);
    }

    /**
     * 求人詳細からの応募（/recruit_jobs/{job:slug}/apply）
     * 例外時はログに詳細を出しつつ、ユーザーにはフレンドリーに返す
     */
    public function store(Request $request, Job $job)
    {
        try {
            if (!$job || !$job->id) {
                abort(404, 'Job not found');
            }

            // バリデーション（email は未入力ならログインのメールで補完するので nullable）
            $data = $request->validate([
                'name'    => ['required', 'string', 'max:50'],
                'email'   => ['nullable', 'email', 'max:255'],
                'phone'   => ['nullable', 'string', 'max:50'],
                'message' => ['nullable', 'string', 'max:5000'],
            ]);

            $app = new Application();
            $table = $app->getTable();

            // 必須系（存在する列だけ安全にセット）
            if (Schema::hasColumn($table, 'job_id'))    $app->job_id = $job->id;

            // ログインユーザー関連
            $user = $request->user();
            if (Schema::hasColumn($table, 'user_id') && $user) {
                $app->user_id = $user->id;
            }

            // email はフォーム優先、無ければログインユーザーのメール
            if (Schema::hasColumn($table, 'email')) {
                $app->email = $data['email'] ?? ($user->email ?? null);
            }

            // その他の任意項目
            if (Schema::hasColumn($table, 'name') && isset($data['name'])) {
                $app->name = $data['name'];
            }
            if (Schema::hasColumn($table, 'phone') && isset($data['phone'])) {
                $app->phone = $data['phone'];
            }
            if (Schema::hasColumn($table, 'message') && isset($data['message'])) {
                $app->message = $data['message'];
            }

            // ステータス初期値（NOT NULL/DEFAULT 無しでも安全に）
            if (Schema::hasColumn($table, 'status') && empty($app->status)) {
                $app->status = 'applied';
            }

            $app->save();

            return redirect()
                ->route('mypage.applications.index')
                ->with('status', '応募を受け付けました。');
        } catch (\Throwable $e) {
            // 例外をログに詳細出力（原因特定用）
            Log::error('[Application apply failed]', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'job_id'  => $job->id ?? null,
                'user_id' => $request->user()->id ?? null,
                'payload' => $request->except(['_token']),
            ]);

            // ユーザーには一般的なエラーで返す
            return back()
                ->withInput()
                ->withErrors(['apply' => '応募処理でエラーが発生しました。しばらくしてから再度お試しください。']);
        }
    }
}
