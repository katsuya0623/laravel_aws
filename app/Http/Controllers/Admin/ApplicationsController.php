<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Application;
use App\Models\Job;

class ApplicationsController extends Controller
{
    public function index(Request $request)
    {
        $q = Application::query()->with([
            'job' => fn($jq) => $jq->select(['id','title','company_id','slug'])
        ]);

        if ($kw = trim((string)$request->input('q'))) {
            $q->where(function ($w) use ($kw) {
                if (Schema::hasColumn((new Application)->getTable(), 'name'))  $w->orWhere('name','like',"%{$kw}%");
                if (Schema::hasColumn((new Application)->getTable(), 'email')) $w->orWhere('email','like',"%{$kw}%");
                $w->orWhereHas('job', fn($jq)=>$jq->where('title','like',"%{$kw}%"));
            });
        }
        if (($st = $request->input('status')) !== null && Schema::hasColumn((new Application)->getTable(), 'status')) {
            $q->where('status', $st);
        }
        if ($cid = $request->input('company_id')) {
            if (Schema::hasColumn((new Job)->getTable(), 'company_id')) {
                $q->whereHas('job', fn($jq)=>$jq->where('company_id', $cid));
            }
        }
        if ($jid = $request->input('job_id')) {
            if (Schema::hasColumn((new Application)->getTable(), 'job_id')) {
                $q->where('job_id', $jid);
            }
        }

        $applications = $q->latest('id')->paginate(30)->withQueryString();

        $statusOptions = Schema::hasColumn((new Application)->getTable(), 'status')
            ? [''=>'すべて','new'=>'新規','viewed'=>'確認済み','interview'=>'面接中','offer'=>'内定','rejected'=>'不採用','withdrawn'=>'辞退']
            : [];

        return view('admin.applications.index', compact('applications','statusOptions'));
    }

    public function export(Request $request): StreamedResponse
    {
        $file = 'applications_'.now()->format('Ymd_His').'.csv';

        $q = Application::query()->with('job');

        if ($kw = trim((string)$request->input('q'))) {
            $q->where(function ($w) use ($kw) {
                if (Schema::hasColumn((new Application)->getTable(), 'name'))  $w->orWhere('name','like',"%{$kw}%");
                if (Schema::hasColumn((new Application)->getTable(), 'email')) $w->orWhere('email','like',"%{$kw}%");
                $w->orWhereHas('job', fn($jq)=>$jq->where('title','like',"%{$kw}%"));
            });
        }
        if (($st = $request->input('status')) !== null && Schema::hasColumn((new Application)->getTable(), 'status')) {
            $q->where('status', $st);
        }
        if ($cid = $request->input('company_id')) {
            if (Schema::hasColumn((new Job)->getTable(), 'company_id')) {
                $q->whereHas('job', fn($jq)=>$jq->where('company_id', $cid));
            }
        }
        // ★ 修正ポイント：$jid を先に取り出してから判定
        $jid = $request->input('job_id');
        if ($jid && Schema::hasColumn((new Application)->getTable(), 'job_id')) {
            $q->where('job_id', $jid);
        }

        $q->latest('id');

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$file}\"",
        ];

        return response()->stream(function () use ($q) {
            $out = fopen('php://output', 'w');
            // Excel向けBOM
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['ID','応募日時','応募者名','メール','求人タイトル','求人ID','ステータス']);

            $q->chunk(100, function ($rows) use ($out) {
                foreach ($rows as $ap) {
                    fputcsv($out, [
                        $ap->id,
                        optional($ap->created_at)->format('Y-m-d H:i'),
                        $ap->name ?? '',
                        $ap->email ?? '',
                        optional($ap->job)->title ?? '',
                        $ap->job_id ?? '',
                        Schema::hasColumn((new Application)->getTable(), 'status') ? (string)$ap->status : '',
                    ]);
                }
            });
            fclose($out);
        }, 200, $headers);
    }
}
