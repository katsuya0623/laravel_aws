<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

        $query = Post::query()->published();

        // ▼ MySQL の posts テーブルに sponsor_company_id があるかどうかで分岐
        if (Schema::hasColumn('posts', 'sponsor_company_id')) {
            // カラムがある環境では、今までどおり sponsor_company_id で絞る
            $query
                ->whereNotNull('sponsor_company_id')
                ->when($companyId, fn ($q) => $q->where('sponsor_company_id', $companyId));
        } else {
            // カラムが無い環境では is_sponsored フラグを使う or 何も出さない
            if (Schema::hasColumn('posts', 'is_sponsored')) {
                $query->where('is_sponsored', 1);
            } else {
                // どちらのカラムも無いなら一旦空で返す（500で落ちるよりマシ）
                $query->whereRaw('1 = 0');
            }
        }

        $posts = $query
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('users.sponsored_articles.index', compact('posts'));
    }
}
