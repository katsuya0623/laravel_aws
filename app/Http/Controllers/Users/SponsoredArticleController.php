<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Post;                 // ← ここを Post に
use Illuminate\Support\Facades\Schema;

class SponsoredArticleController extends Controller
{
    /** スポンサー記事一覧 */
    public function index()
    {
        $posts = Post::query()
            // is_sponsored カラムがある環境だけ絞り込み（安全策）
            ->when(Schema::hasColumn('posts', 'is_sponsored'), fn ($q) => $q->where('is_sponsored', 1))
            ->latest('published_at')
            ->paginate(12);

        return view('users.sponsored_articles.index', compact('posts'));
    }
}
