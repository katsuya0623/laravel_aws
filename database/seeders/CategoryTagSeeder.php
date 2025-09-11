<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryTagSeeder extends Seeder
{
    public function run(): void
    {
        // ---- カテゴリ（例）
        $categories = [
            ['name' => 'お知らせ', 'slug' => 'news'],
            ['name' => '技術ブログ', 'slug' => 'tech'],
            ['name' => 'リリース', 'slug' => 'release'],
        ];
        foreach ($categories as $c) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $c['slug']],
                ['name' => $c['name'], 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // ---- タグ（例）
        $tags = ['Laravel','運用','告知','採用','不具合修正','改善'];
        foreach ($tags as $t) {
            $slug = Str::slug($t, '-');
            DB::table('tags')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $t, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // ---- 既存ポストにランダムでカテゴリ & タグ付け（リレーション未定義でも動く実装）
        $catIds = DB::table('categories')->pluck('id')->all();
        $tagIds = DB::table('tags')->pluck('id')->all();

        DB::table('posts')->orderBy('id')->chunk(200, function($posts) use ($catIds, $tagIds) {
            foreach ($posts as $p) {
                // カテゴリ（1つ）
                if ($catIds) {
                    DB::table('posts')->where('id', $p->id)
                        ->update(['category_id' => $catIds[array_rand($catIds)]]);
                }
                // タグ（0〜3個）
                if ($tagIds) {
                    $pick = array_rand($tagIds, min(count($tagIds), rand(0,3)) ?: 0);
                    $picked = is_array($pick) ? $pick : ($pick === 0 ? [] : [$pick]);
                    foreach ($picked as $k) {
                        DB::table('post_tag')->updateOrInsert(
                            ['post_id' => $p->id, 'tag_id' => $tagIds[$k]],
                            []
                        );
                    }
                }
            }
        });
    }
}
