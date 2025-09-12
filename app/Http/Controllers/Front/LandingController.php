<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Company;
use App\Models\Job;

class LandingController extends Controller
{
    public function index()
    {
        // それぞれ無ければ空コレクションでOK（ビュー側で@forelse処理）
        $latestArticles    = class_exists(Post::class)
            ? Post::query()->when(method_exists(Post::class,'published'),
                fn($q)=>$q->published())->latest('published_at')->limit(6)->get()
            : collect();

        $featuredCompanies = class_exists(Company::class)
            ? Company::query()->when(method_exists(Company::class,'published'),
                fn($q)=>$q->published())->latest('published_at')->latest('id')->limit(6)->get()
            : collect();

        $latestJobs        = class_exists(Job::class)
            ? Job::query()->when(method_exists(Job::class,'published'),
                fn($q)=>$q->published())->latest('published_at')->latest('id')->limit(6)->get()
            : collect();

        return view('front.home', compact('latestArticles','featuredCompanies','latestJobs'));
    }
}
