<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use App\Models\Application;
use App\Models\Job;

class ApplicantController extends Controller
{
    /** 会社IDを推定（users.company / users.company_id / companies.user_id / company_profiles.user_id） */
    private function resolveCompanyId($user): ?int
    {
        return optional($user->company)->id
            ?? $user->company_id
            ?? (Schema::hasTable('companies') ? DB::table('companies')->where('user_id', $user->id)->value('id') : null)
            ?? (Schema::hasTable('company_profiles') ? DB::table('company_profiles')->where('user_id', $user->id)->value('company_id') : null);
    }

    /** Job 用の select カラムを安全に組み立て */
    private function buildJobSelectColumns(): array
    {
        $jobsTbl   = (new Job)->getTable();
        $jobSelect = ['id'];

        foreach (['title', 'slug', 'company_id', 'user_id'] as $col) {
            if (Schema::hasColumn($jobsTbl, $col)) {
                $jobSelect[] = $col;
            }
        }

        return $jobSelect;
    }

    /** 応募者一覧 */
    public function index(Request $request)
    {
        $user     = $request->user();
        $appsTbl  = (new Application)->getTable();
        $jobsTbl  = (new Job)->getTable();
        $jobSelect = $this->buildJobSelectColumns();

        $companyId = $this->resolveCompanyId($user);

        // 自社の求人だけ（company_id 優先、無ければ user_id フォールバック）
        $ownedJobsQ = Job::query();
        if ($companyId && Schema::hasColumn($jobsTbl, 'company_id')) {
            $ownedJobsQ->where($jobsTbl . '.company_id', $companyId);
        } elseif (Schema::hasColumn($jobsTbl, 'user_id')) {
            $ownedJobsQ->where($jobsTbl . '.user_id', $user->id);
        } else {
            // どちらの列も無い場合は空にする
            $ownedJobsQ->whereRaw('1=0');
        }
        $ownedJobs = $ownedJobsQ->orderByDesc('id')->limit(200)->get(['id', 'title']);

        $jobId   = $request->integer('job_id') ?: null;
        $keyword = trim((string) $request->get('q', ''));
        $status  = $request->get('status');

        $query = Application::query()
            ->with([
                'job' => function ($q) use ($jobSelect) {
                    $q->select($jobSelect);
                },
            ])
            ->whereHas('job') // 存在しない job を除外
            ->latest('id');

        // 自社求人に紐づく応募だけ
        if ($companyId && Schema::hasColumn($jobsTbl, 'company_id')) {
            $query->whereHas('job', fn($q) => $q->where($jobsTbl . '.company_id', $companyId));
        } elseif (Schema::hasColumn($jobsTbl, 'user_id')) {
            $query->whereHas('job', fn($q) => $q->where($jobsTbl . '.user_id', $user->id));
        } else {
            $query->whereRaw('1=0');
        }

        if ($jobId && Schema::hasColumn($appsTbl, 'job_id')) {
            $query->where($appsTbl . '.job_id', $jobId);
        }

        if ($status !== null && Schema::hasColumn($appsTbl, 'status')) {
            $query->where($appsTbl . '.status', $status);
        }

        if ($keyword !== '') {
            $query->where(function ($qq) use ($keyword, $appsTbl) {
                foreach (['name', 'full_name', 'applicant_name', 'email', 'tel', 'phone'] as $col) {
                    if (Schema::hasColumn($appsTbl, $col)) {
                        $qq->orWhere($appsTbl . '.' . $col, 'like', "%{$keyword}%");
                    }
                }
            });
        }

        $applications = $query->paginate(20)->withQueryString();

        $statusOptions = [];
        if (Schema::hasColumn($appsTbl, 'status')) {
            $statusOptions = [
                ''          => 'すべて',
                'pending'   => '未対応',
                'reviewing' => '確認中',
                'passed'    => '合格',
                'rejected'  => '見送り',
            ];
        }

        return view('users.applicants.index', compact(
            'applications',
            'ownedJobs',
            'jobId',
            'keyword',
            'status',
            'statusOptions'
        ));
    }

    /** 応募詳細 */
    public function show(Request $request, Application $application)
    {
        $user      = $request->user();
        $appsTbl   = (new Application)->getTable();
        $jobsTbl   = (new Job)->getTable();
        $jobSelect = $this->buildJobSelectColumns();
        $companyId = $this->resolveCompanyId($user);

        // アクセス権チェック：自社求人の応募か？
        $ok = false;
        if ($companyId && Schema::hasColumn($jobsTbl, 'company_id')) {
            $ok = Job::where('id', $application->job_id)->where($jobsTbl . '.company_id', $companyId)->exists();
        } elseif (Schema::hasColumn($jobsTbl, 'user_id')) {
            $ok = Job::where('id', $application->job_id)->where($jobsTbl . '.user_id', $user->id)->exists();
        }
        if (! $ok) {
            throw new ModelNotFoundException(); // 404
        }

        $application->loadMissing([
            'job' => function ($q) use ($jobSelect) {
                $q->select($jobSelect);
            },
        ]);

        $statusOptions = [];
        if (Schema::hasColumn($appsTbl, 'status')) {
            $statusOptions = [
                'pending'   => '未対応',
                'reviewing' => '確認中',
                'passed'    => '合格',
                'rejected'  => '見送り',
            ];
        }

        return view('users.applicants.show', compact('application', 'statusOptions'));
    }

    /** ステータス更新（メモもあれば一緒に） */
    public function updateStatus(Request $request, Application $application)
    {
        $user      = $request->user();
        $appsTbl   = (new Application)->getTable();
        $jobsTbl   = (new Job)->getTable();
        $companyId = $this->resolveCompanyId($user);

        // 権限チェック
        $ok = false;
        if ($companyId && Schema::hasColumn($jobsTbl, 'company_id')) {
            $ok = Job::where('id', $application->job_id)->where($jobsTbl . '.company_id', $companyId)->exists();
        } elseif (Schema::hasColumn($jobsTbl, 'user_id')) {
            $ok = Job::where('id', $application->job_id)->where($jobsTbl . '.user_id', $user->id)->exists();
        }
        if (! $ok) {
            abort(403);
        }

        $data = [];
        if (Schema::hasColumn($appsTbl, 'status')) {
            $data['status'] = (string) $request->string('status');
        }
        if (Schema::hasColumn($appsTbl, 'note') && $request->filled('note')) {
            $data['note'] = (string) $request->get('note');
        }

        if (! empty($data)) {
            $application->fill($data)->save();
        }

        return redirect()
            ->route('users.applicants.show', $application)
            ->with('ok', '更新しました');
    }
}
