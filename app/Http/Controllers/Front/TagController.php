<?php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TagController extends Controller
{
    public function show(string $slug, Request $request)
    {
        abort_if(!Schema::hasTable('tags') || !Schema::hasTable('post_tag'), 404);

        $tag = DB::table('tags')->where('slug',$slug)->first();
        abort_if(!$tag, 404);

        $perPage = 10;
        $page = max(1, (int)$request->get('page', 1));

        $postIds = DB::table('post_tag')
            ->where('tag_id', $tag->id)
            ->pluck('post_id');

        $base = DB::table('posts')->whereIn('id',$postIds)
            ->when(Schema::hasColumn('posts','published_at'), fn($q) => $q->orderByDesc('published_at'))
            ->orderByDesc('id');

        $total = (clone $base)->count();
        $items = $base->forPage($page, $perPage)->get();

        $posts = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => url('/tag/'.$slug)
        ]);

        // ヘッダーカテゴリ
        $categories = [];
        if (Schema::hasTable('categories')) {
            $categories = DB::table('categories')->orderBy('name')->get();
        }
        // サイドバータグ
        $tags = [];
        if (Schema::hasTable('tags')) {
            $tags = DB::table('tags')->orderBy('name')->get();
        }

        return view('front.tax.tag', compact('tag','posts','categories','tags'));
    }
}
