<?php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        // posts から最新10件（published_at が無ければ id 降順）
        $posts = DB::table('posts')
            ->when(Schema::hasColumn('posts','published_at'), fn($q) => $q->orderByDesc('published_at'))
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // ヘッダーのカテゴリ（テーブル無ければ空配列）
        $categories = [];
        if (Schema::hasTable('categories')) {
            $categories = DB::table('categories')->orderBy('name')->get();
        }

        return view('front.home', compact('posts','categories'));
    }
}
