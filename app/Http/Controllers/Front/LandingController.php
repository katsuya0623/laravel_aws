<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use App\Models\Post;
use App\Models\Company;   // ★ 追加（存在する前提）
use App\Models\RecruitJob; // ★ 必要なら（無ければコメントアウト可）

class LandingController extends Controller
{
    public function index()
    {
        // ===== 記事（既存ロジック） =====
        $q = Post::query()->with('author');

        if (Schema::hasColumn('posts', 'published_at')) {
            // Post に published() スコープがある想定
            $q->published()->orderByDesc('published_at');
        } else {
            $q->orderByDesc('id');
        }
        $posts = $q->limit(10)->get();

        // ===== 企業（TOP 用：フォールバックに頼らず明示的に渡す）=====
        $cq = Company::query();

        // どの環境でも出るように緩めの公開条件（該当カラムだけ適用）
        if (Schema::hasColumn('companies', 'is_public'))   $cq->where('is_public', 1);
        if (Schema::hasColumn('companies', 'is_published'))$cq->where('is_published', 1);
        if (Schema::hasColumn('companies', 'published'))   $cq->where('published', 1);
        if (Schema::hasColumn('companies', 'status'))      $cq->where('status', 'published');
        if (Schema::hasColumn('companies', 'is_demo'))     $cq->where(function($q){ $q->whereNull('is_demo')->orWhere('is_demo', 0); });
        if (Schema::hasColumn('companies', 'deleted_at'))  $cq->whereNull('deleted_at');
        if (Schema::hasColumn('companies', 'name'))        $cq->where('name','not like','%デモ%')->where('name','not like','%demo%');

        // 並び順（updated_at があれば優先）
        if (Schema::hasColumn('companies', 'updated_at')) {
            $cq->orderByDesc('updated_at');
        }
        $cq->orderByDesc('id');

        // TOP は軽く 6 件（必要なら変えてOK）
        $companiesTop = $cq->limit(6)->get();

        // ===== 求人（任意：あれば表示される。無ければ partial が「準備中」と出す）=====
        $jobsTop = collect(); // 無ければ空のままでOK
        if (class_exists(RecruitJob::class)) {
            $jq = RecruitJob::query();
            if (Schema::hasColumn('recruit_jobs', 'is_published')) $jq->where('is_published', 1);
            if (Schema::hasColumn('recruit_jobs', 'published'))    $jq->where('published', 1);
            if (Schema::hasColumn('recruit_jobs', 'status'))       $jq->where('status', 'published');
            if (Schema::hasColumn('recruit_jobs', 'is_demo'))      $jq->where(function($q){ $q->whereNull('is_demo')->orWhere('is_demo', 0); });
            if (Schema::hasColumn('recruit_jobs', 'deleted_at'))   $jq->whereNull('deleted_at');
            if (Schema::hasColumn('recruit_jobs', 'updated_at'))   $jq->orderByDesc('updated_at');
            $jq->orderByDesc('id');
            $jobsTop = $jq->limit(6)->get();
        }

        // /blog の見た目のビューに切り替え（既存）
        return view('front.home', compact('posts', 'companiesTop', 'jobsTop'));
    }
}
