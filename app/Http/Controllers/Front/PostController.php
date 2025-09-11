<?php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $perPage    = 12;
        $page       = max(1, (int)$request->get('page', 1));
        $q          = trim((string)$request->get('q', ''));
        $categoryId = $request->get('category');

        // ベースQuery
        $base = DB::table('posts');

        // 公開条件 + 並び
        if (Schema::hasColumn('posts','published_at')) {
            $base->whereNotNull('published_at')
                 ->where('published_at','<=', now())
                 ->orderByDesc('published_at');
        }
        $base->orderByDesc('id');

        // キーワード検索（title/bodyがあれば）
        if ($q !== '') {
            $keywords = preg_split('/\s+/', $q);
            $base->where(function($qq) use ($keywords){
                foreach ($keywords as $kw) {
                    $qq->where(function($w) use ($kw){
                        $w->when(Schema::hasColumn('posts','title'), fn($q)=>$q->orWhere('title','like',"%{$kw}%"))
                          ->when(Schema::hasColumn('posts','body'),  fn($q)=>$q->orWhere('body','like', "%{$kw}%"));
                    });
                }
            });
        }

        // カテゴリ絞り込み（多対多/単一どちらでも）
        if (!empty($categoryId)) {
            if (Schema::hasTable('category_post')) {
                $base->whereExists(function($sub) use ($categoryId){
                    $sub->from('category_post')
                        ->whereColumn('category_post.post_id','posts.id')
                        ->where('category_post.category_id',$categoryId);
                });
            } elseif (Schema::hasColumn('posts','category_id')) {
                $base->where('category_id',$categoryId);
            }
        }

        // 件数 & ページ分取得
        $total = (clone $base)->count();
        $items = $base->forPage($page, $perPage)->get();

        // ページネータ（/posts に統一）
        $posts = new LengthAwarePaginator($items, $total, $perPage, $page);
        $posts->withPath(route('front.posts.index'));
        $posts->appends($request->query());

        // ヘッダー用カテゴリ
        $categories = Schema::hasTable('categories')
            ? DB::table('categories')->orderBy('name')->get()
            : collect();

        // サイドバー用タグ
        $tags = Schema::hasTable('tags')
            ? DB::table('tags')->orderBy('name')->get()
            : collect();

        // 一覧で使う「投稿ごとのカテゴリ配列」マップ
        $catsByPost = [];
        if ($items->count() > 0) {
            $postIds = $items->pluck('id')->all();

            if (Schema::hasTable('category_post') && Schema::hasTable('categories')) {
                $rows = DB::table('categories')
                    ->join('category_post','categories.id','=','category_post.category_id')
                    ->whereIn('category_post.post_id', $postIds)
                    ->select('categories.*','category_post.post_id')
                    ->get();

                foreach ($rows as $r) {
                    $catsByPost[$r->post_id][] = (object)[
                        'id'   => $r->id,
                        'name' => $r->name,
                        'slug' => $r->slug ?? null,
                    ];
                }
            } elseif (Schema::hasColumn('posts','category_id') && Schema::hasTable('categories')) {
                $rows = DB::table('posts')
                    ->leftJoin('categories','categories.id','=','posts.category_id')
                    ->whereIn('posts.id',$postIds)
                    ->select('posts.id as post_id','categories.id','categories.name','categories.slug')
                    ->get();

                foreach ($rows as $r) {
                    if ($r->id) {
                        $catsByPost[$r->post_id][] = (object)[
                            'id'   => $r->id,
                            'name' => $r->name,
                            'slug' => $r->slug ?? null,
                        ];
                    }
                }
            }
        }

        return view('front.posts.index', compact('posts','categories','tags','catsByPost'));
    }

    /**
     * 詳細（slug を優先、無ければ ID）
     * ルート: /posts/{slugOrId}
     */
    public function show(string $slugOrId)
    {
        // Eloquentで取得（ビュー互換のため）
        $base = Post::query();

        if (Schema::hasColumn('posts','published_at')) {
            $base->whereNotNull('published_at')
                 ->where('published_at','<=', now());
        }

        // 数字だけなら ID、そうでなければ slug
        $post = ctype_digit($slugOrId)
            ? $base->where('id', (int)$slugOrId)->first()
            : $base->where('slug', $slugOrId)->first();

        abort_unless($post, 404);

        // ヘッダー用カテゴリ
        $categories = Schema::hasTable('categories')
            ? DB::table('categories')->orderBy('name')->get()
            : collect();

        // 詳細用タグ
        $postTags = collect();
        if (Schema::hasTable('tags') && Schema::hasTable('post_tag')) {
            $postTags = DB::table('tags')
                ->join('post_tag','tags.id','=','post_tag.tag_id')
                ->where('post_tag.post_id',$post->id)
                ->select('tags.*')->get();
        }

        // 詳細用カテゴリ
        $postCats = collect();
        if (Schema::hasTable('categories') && Schema::hasTable('category_post')) {
            $postCats = DB::table('categories')
                ->join('category_post','categories.id','=','category_post.category_id')
                ->where('category_post.post_id',$post->id)
                ->select('categories.*')->get();
        } elseif (Schema::hasColumn('posts','category_id') && Schema::hasTable('categories')) {
            $c = DB::table('categories')
                ->join('posts','categories.id','=','posts.category_id')
                ->where('posts.id',$post->id)
                ->select('categories.*')->first();
            if ($c) $postCats = collect([$c]);
        }

        // 前後記事（published_at優先）
        $prev = $next = null;
        if (Schema::hasColumn('posts','published_at')) {
            $prev = DB::table('posts')
                ->whereNotNull('published_at')->where('published_at','<=', now())
                ->where('published_at','<',$post->published_at)
                ->orderBy('published_at','desc')
                ->select('id','title','slug','published_at')
                ->first();

            $next = DB::table('posts')
                ->whereNotNull('published_at')->where('published_at','<=', now())
                ->where('published_at','>',$post->published_at)
                ->orderBy('published_at','asc')
                ->select('id','title','slug','published_at')
                ->first();
        } else {
            $prev = DB::table('posts')->where('id','<',$post->id)->orderBy('id','desc')->select('id','title','slug')->first();
            $next = DB::table('posts')->where('id','>',$post->id)->orderBy('id','asc')->select('id','title','slug')->first();
        }

        // 関連（タグ経由）
        $related = collect();
        if ($postTags->count() && Schema::hasTable('post_tag')) {
            $tagIds = $postTags->pluck('id')->all();
            $related = DB::table('posts')
                ->join('post_tag','posts.id','=','post_tag.post_id')
                ->whereIn('post_tag.tag_id',$tagIds)
                ->where('posts.id','<>',$post->id)
                ->when(Schema::hasColumn('posts','published_at'), fn($q) =>
                    $q->whereNotNull('posts.published_at')->where('posts.published_at','<=', now())
                )
                ->select('posts.id','posts.title','posts.slug','posts.published_at')
                ->distinct()
                ->orderByDesc('posts.published_at')
                ->limit(6)
                ->get();
        }

        return view('front.posts.show', [
            'post'      => $post,        // ← Eloquentモデル
            'categories'=> $categories,
            'postTags'  => $postTags,
            'postCats'  => $postCats,
            'prev'      => $prev,
            'next'      => $next,
            'related'   => $related,
        ]);
    }
}
