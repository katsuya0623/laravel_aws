<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Application;
use App\Models\Job;

class ApplicantController extends Controller
{
    /** 応募者一覧（既出） */
    public function index(Request $request)
    {
        $user     = $request->user();
        $appsTbl  = (new Application)->getTable();
        $jobsTbl  = (new Job)->getTable();

        $ownedJobs = Job::query()
            ->when(Schema::hasColumn($jobsTbl, 'user_id'), fn($q) => $q->where('user_id', $user->id))
            ->orderByDesc('id')->limit(200)->get(['id','title']);

        $jobId   = $request->integer('job_id') ?: null;
        $keyword = trim((string)$request->get('q', ''));
        $status  = $request->get('status');

        $query = Application::query()
            ->with(['job' => fn($q) => $q->select('id','title','slug')])
            ->latest('id');

        if (Schema::hasColumn($appsTbl, 'job_id') && Schema::hasColumn($jobsTbl, 'user_id')) {
            $query->whereHas('job', fn($q) => $q->where($jobsTbl.'.user_id', $user->id));
        }

        if ($jobId && Schema::hasColumn($appsTbl, 'job_id')) {
            $query->where($appsTbl.'.job_id', $jobId);
        }

        if ($status !== null && Schema::hasColumn($appsTbl, 'status')) {
            $query->where('status', $status);
        }

        if ($keyword !== '') {
            $query->where(function ($qq) use ($keyword, $appsTbl) {
                foreach (['name','full_name','applicant_name','email','tel','phone'] as $col) {
                    if (Schema::hasColumn($appsTbl, $col)) $qq->orWhere($col, 'like', "%{$keyword}%");
                }
            });
        }

        $applications = $query->paginate(20)->withQueryString();

        $statusOptions = [];
        if (Schema::hasColumn($appsTbl, 'status')) {
            $statusOptions = [
                ''           => 'すべて',
                'pending'    => '未対応',
                'reviewing'  => '確認中',
                'passed'     => '合格',
                'rejected'   => '見送り',
            ];
        }

        return view('users.applicants.index', compact('applications','ownedJobs','jobId','keyword','status','statusOptions'));
    }

    /** 応募詳細 */
    public function show(Request $request, Application $application)
    {
        $user    = $request->user();
        $appsTbl = (new Application)->getTable();
        $jobsTbl = (new Job)->getTable();

        // アクセス権チェック：このユーザーの求人に紐づく応募か？
        if (Schema::hasColumn($appsTbl, 'job_id') && Schema::hasColumn($jobsTbl, 'user_id')) {
            $ok = Job::where('id', $application->job_id)->where('user_id', $user->id)->exists();
            if (!$ok) throw new ModelNotFoundException(); // 404
        }

        $application->loadMissing(['job' => fn($q) => $q->select('id','title','slug')]);

        // ステータス候補
        $statusOptions = [];
        if (Schema::hasColumn($appsTbl, 'status')) {
            $statusOptions = [
                'pending'    => '未対応',
                'reviewing'  => '確認中',
                'passed'     => '合格',
                'rejected'   => '見送り',
            ];
        }

        return view('users.applicants.show', compact('application','statusOptions'));
    }

    /** ステータス更新（メモもあれば一緒に） */
    public function updateStatus(Request $request, Application $application)
    {
        $user    = $request->user();
        $appsTbl = (new Application)->getTable();
        $jobsTbl = (new Job)->getTable();

        if (Schema::hasColumn($appsTbl, 'job_id') && Schema::hasColumn($jobsTbl, 'user_id')) {
            $ok = Job::where('id', $application->job_id)->where('user_id', $user->id)->exists();
            if (!$ok) abort(403);
        }

        $data = [];
        if (Schema::hasColumn($appsTbl, 'status')) {
            $data['status'] = $request->string('status')->toString();
        }
        if (Schema::hasColumn($appsTbl, 'note') && $request->filled('note')) {
            $data['note'] = (string)$request->get('note');
        }

        if (!empty($data)) {
            $application->fill($data)->save();
        }

        return redirect()->route('users.applicants.show', $application)->with('ok', '更新しました');
    }
}
