<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class SponsoredArticleController extends Controller
{
    /** スポンサー記事一覧 */
    public function index(Request $request)
    {
        $companyId = (int) (
            session('acting_company_id')
            ?? optional($request->user())->company_id
            ?? $request->integer('company_id')
        ) ?: null;

        $posts = Post::query()
            ->published()
            // 🔹 スポンサー付きの記事のみ表示
            ->whereNotNull('sponsor_company_id')
            // 🔹 特定企業（例：今北産業）のスポンサー記事だけに絞り込み
            ->when($companyId, fn($q) => $q->where('sponsor_company_id', $companyId))
            ->latest('published_at')
            ->paginate(12);

        return view('users.sponsored_articles.index', compact('posts'));
    }
}
