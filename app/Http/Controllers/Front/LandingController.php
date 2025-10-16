<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Post;
use App\Models\Company;
use App\Models\RecruitJob;

class LandingController extends Controller
{
    public function index()
    {
        /* ===== 記事 ===== */
        $q = Post::query()->with('author');
        if (Schema::hasColumn('posts', 'published_at')) {
            if (method_exists(Post::class, 'published')) {
                $q->published()->orderByDesc('published_at');
            } else {
                $q->whereNotNull('published_at')
                  ->where('published_at', '<=', now())
                  ->orderByDesc('published_at');
            }
        } else {
            $q->orderByDesc('id');
        }
        $posts = $q->limit(10)->get();

        /* ===== 企業 ===== */
        $cq = Company::query();
        if (Schema::hasColumn('companies', 'is_public'))    $cq->where('is_public', 1);
        if (Schema::hasColumn('companies', 'is_published')) $cq->where('is_published', 1);
        if (Schema::hasColumn('companies', 'published'))    $cq->where('published', 1);
        if (Schema::hasColumn('companies', 'status'))       $cq->where('status', 'published');
        if (Schema::hasColumn('companies', 'is_demo'))      $cq->where(fn($qq)=>$qq->whereNull('is_demo')->orWhere('is_demo',0));
        if (Schema::hasColumn('companies', 'deleted_at'))   $cq->whereNull('deleted_at');
        if (Schema::hasColumn('companies', 'name'))         $cq->where('name','not like','%デモ%')->where('name','not like','%demo%');
        if (Schema::hasColumn('companies', 'updated_at'))   $cq->orderByDesc('updated_at');
        $companiesTop = $cq->orderByDesc('id')->limit(6)->get();

        /* ===== 求人（Model結果 + DB直叩き結果を常にマージ） ===== */
        $limit = 12;

        // 1) Model経由（スコープ適用 + 会社をEager Load）
        $modelJobs = collect();
        if (class_exists(RecruitJob::class)) {
            $jq = RecruitJob::query()
                ->with(['company']); // ★ 会社ロゴ参照用

            foreach (['published', 'public', 'active', 'visible'] as $scope) {
                if (method_exists(RecruitJob::class, $scope)) {
                    $jq->{$scope}();
                }
            }

            // ゆるめ条件（期限/公開開始/削除/デモ除外）
            if (Schema::hasColumn('recruit_jobs', 'expires_at')) {
                $jq->where(function ($qq) {
                    $qq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            }
            if (Schema::hasColumn('recruit_jobs', 'published_at')) {
                $jq->where(function ($qq) {
                    $qq->whereNull('published_at')->orWhere('published_at', '<=', now());
                });
            }
            if (Schema::hasColumn('recruit_jobs', 'deleted_at')) $jq->whereNull('deleted_at');
            if (Schema::hasColumn('recruit_jobs', 'is_demo'))    $jq->where(fn($qq)=>$qq->whereNull('is_demo')->orWhere('is_demo',0));

            if (Schema::hasColumn('recruit_jobs', 'published_at')) {
                $jq->orderByDesc('published_at');
            } elseif (Schema::hasColumn('recruit_jobs', 'updated_at')) {
                $jq->orderByDesc('updated_at');
            }
            $jq->orderByDesc('id');

            $modelJobs = $jq->limit($limit)->get([
                'id','slug','title','thumbnail_url','thumbnail_path','thumbnail',
                'published_at','expires_at','updated_at','created_at','company_id'
            ]);
        }

        // 2) DB直叩き（recruit_jobs / jobs）
        $dbJobs = collect();

        if (Schema::hasTable('recruit_jobs')) {
            $r = DB::table('recruit_jobs')
                ->select(['id','slug','title','thumbnail_url','thumbnail_path','thumbnail','published_at','expires_at','updated_at','created_at','company_id'])
                ->when(Schema::hasColumn('recruit_jobs','expires_at'), function($q){
                    $q->where(function($qq){ $qq->whereNull('expires_at')->orWhere('expires_at','>', now()); });
                })
                ->when(Schema::hasColumn('recruit_jobs','published_at'), function($q){
                    $q->where(function($qq){ $qq->whereNull('published_at')->orWhere('published_at','<=', now()); });
                })
                ->when(Schema::hasColumn('recruit_jobs','deleted_at'), fn($q)=>$q->whereNull('deleted_at'))
                ->when(Schema::hasColumn('recruit_jobs','is_demo'), fn($q)=>$q->where(function($qq){ $qq->whereNull('is_demo')->orWhere('is_demo',0); }))
                ->orderByDesc(DB::raw('COALESCE(published_at, updated_at, created_at)'))
                ->orderByDesc('id')
                ->limit($limit * 2)
                ->get();
            $dbJobs = $dbJobs->concat($r);
        }

        if (Schema::hasTable('jobs')) {
            $j = DB::table('jobs')
                ->select(['id','slug','title','thumbnail_url','thumbnail_path','thumbnail','published_at','expires_at','updated_at','created_at','company_id'])
                ->when(Schema::hasColumn('jobs','expires_at'), function($q){
                    $q->where(function($qq){ $qq->whereNull('expires_at')->orWhere('expires_at','>', now()); });
                })
                ->when(Schema::hasColumn('jobs','published_at'), function($q){
                    $q->where(function($qq){ $qq->whereNull('published_at')->orWhere('published_at','<=', now()); });
                })
                ->when(Schema::hasColumn('jobs','deleted_at'), fn($q)=>$q->whereNull('deleted_at'))
                ->when(Schema::hasColumn('jobs','is_demo'), fn($q)=>$q->where(function($qq){ $qq->whereNull('is_demo')->orWhere('is_demo',0); }))
                ->orderByDesc(DB::raw('COALESCE(published_at, updated_at, created_at)'))
                ->orderByDesc('id')
                ->limit($limit * 2)
                ->get();
            $dbJobs = $dbJobs->concat($j);
        }

        // 3) マージ → 重複除去 → 新しい順 → 上位N件
        $jobsTop = collect()
            ->concat($modelJobs ?? collect())
            ->concat($dbJobs ?? collect())
            ->unique(function($x){ return (data_get($x,'slug') ?? '').'#'.(data_get($x,'id') ?? ''); })
            ->sortByDesc(function($x){
                $p = data_get($x,'published_at');
                $u = data_get($x,'updated_at');
                $c = data_get($x,'created_at');
                $ts = $p ?: ($u ?: $c ?: '1970-01-01');
                return strtotime($ts)*100000 + (int)(data_get($x,'id') ?? 0);
            })
            ->take($limit)
            ->values();

        return view('front.home', compact('posts', 'companiesTop', 'jobsTop'));
    }
}
