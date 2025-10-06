<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // カラム追加（author_type, author_id）
        Schema::table('posts', function (Blueprint $table) {
            // 既に作成済みなら二重作成を避ける
            if (!Schema::hasColumn('posts', 'author_type') && !Schema::hasColumn('posts', 'author_id')) {
                // nullableMorphs = author_type(varchar), author_id(bigint unsigned)
                $table->nullableMorphs('author');
                // 明示的なインデックス名を付けておくと down が確実に動く
                $table->index(['author_type', 'author_id'], 'posts_author_idx');
            }
        });

        // 既存の user_id -> author_* へバックフィル（users からの投稿を移行）
        if (Schema::hasColumn('posts', 'user_id')) {
            // author_type を設定（クエリビルダで列コピーはできないので2段階）
            DB::table('posts')
                ->whereNotNull('user_id')
                ->update(['author_type' => \App\Models\User::class]);

            // author_id = user_id を一括コピー
            DB::statement('UPDATE posts SET author_id = user_id WHERE user_id IS NOT NULL');
        }

        // ※管理者で作った記事の移行（必要なら）
        // 例：昔 admin も users にいた等、メールで突合して移す場合は別マイグレーション/スクリプトで実施してください。
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'author_type') || Schema::hasColumn('posts', 'author_id')) {
                $table->dropIndex('posts_author_idx');
                $table->dropMorphs('author'); // author_type/author_id を削除
            }
        });
    }
};
