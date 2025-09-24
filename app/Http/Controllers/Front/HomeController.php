<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index()
    {
        // —— 記事（公開済みだけ・最新6件）
        $latest = Post::query()
            ->when(Schema::hasColumn('posts','published_at'), function ($q) {
                $q->whereNotNull('published_at')
                  ->where('published_at','<=', now());
            })
            ->latest('published_at')
            ->latest('id')
            ->take(6)
            ->get();

        // —— 企業（TOP 表示ぶんだけ事前取得）
        $companiesTop = $this->topCompanies();

        // —— 求人（TOP 表示ぶんだけ事前取得：Eloquent 最小列で確実に）
        $jobsTop = Job::withoutGlobalScopes()
            ->select(['id','title','slug'])   // どの環境でも存在する列だけ
            ->orderByDesc('id')               // シンプルに id 降順
            ->limit(5)
            ->get();

        return view('front.home', compact('latest', 'companiesTop', 'jobsTop'));
    }

    /** TOP 用の企業リスト（デモ除外＆公開のみ。列存在チェックつき） */
    private function topCompanies()
    {
        try {
            $conn   = (new Company())->getConnectionName() ?: config('database.default');
            $schema = Schema::connection($conn);
            $q      = DB::connection($conn)->table('companies');

            // 公開フラグ
            if ($schema->hasColumn('companies','is_published')) {
                $q->where('is_published', 1);
            }
            // 論理削除
            if ($schema->hasColumn('companies','deleted_at')) {
                $q->whereNull('deleted_at');
            }
            // デモ除外
            if ($schema->hasColumn('companies','name')) {
                $q->where(function ($w) {
                    $w->whereNull('name')
                      ->orWhere(function ($x) {
                          $x->where('name','not like','%デモ%')
                            ->where('name','not like','%demo%');
                      });
                });
            }
            if ($schema->hasColumn('companies','slug')) {
                $q->where(function ($w) {
                    $w->whereNull('slug')
                      ->orWhere('slug','not like','%demo%');
                });
            }

            // 列
            $cols = [];
            foreach (['id','name','slug','logo_path','location','updated_at','published_at'] as $c) {
                if ($schema->hasColumn('companies',$c)) $cols[] = $c;
            }
            $q->select($cols ?: ['*']);

            // 並び
            if ($schema->hasColumn('companies','published_at')) $q->orderByDesc('published_at');
            if ($schema->hasColumn('companies','updated_at'))   $q->orderByDesc('updated_at');
            if ($schema->hasColumn('companies','id'))           $q->orderByDesc('id');

            return collect($q->limit(5)->get());
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
