<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use App\Models\Post;

class LandingController extends Controller
{
    public function index()
    {
        // Eloquent + リレーション一括読み込み（N+1回避）
        $q = Post::query()->with('author');

        // published_at がある環境では公開済みのみ＆公開日の新しい順
        if (Schema::hasColumn('posts', 'published_at')) {
            $q->published()->orderByDesc('published_at');
        } else {
            // 無い環境はIDの降順で代替
            $q->orderByDesc('id');
        }

        $posts = $q->limit(10)->get();

        // /blog の見た目のビューに切り替え
        return view('front.home', compact('posts'));
    }
}
