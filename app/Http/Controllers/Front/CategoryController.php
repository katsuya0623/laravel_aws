<?php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryController extends Controller
{
    public function show(string $slug, Request $request)
    {
        abort_if(!Schema::hasTable('categories') || !Schema::hasTable('category_post'), 404);

        $category = DB::table('categories')->where('slug',$slug)->first();
        abort_if(!$category, 404);

        $perPage = 10;
        $page = max(1, (int)$request->get('page', 1));

        $postIds = DB::table('category_post')
            ->where('category_id', $category->id)
            ->pluck('post_id');

        $base = DB::table('posts')->whereIn('id',$postIds)
            ->when(Schema::hasColumn('posts','published_at'), fn($q) => $q->orderByDesc('published_at'))
            ->orderByDesc('id');

        $total = (clone $base)->count();
        $items = $base->forPage($page, $perPage)->get();

        $posts = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => url('/category/'.$slug)
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

        return view('front.tax.category', compact('category','posts','categories','tags'));
    }
}
