<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 記事テーブルが articles の環境にも posts の環境にも対応
        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                if (!Schema::hasColumn('articles', 'is_sponsored')) {
                    $table->boolean('is_sponsored')->default(0)->after('published_at');
                }
            });
        } elseif (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                if (!Schema::hasColumn('posts', 'is_sponsored')) {
                    $table->boolean('is_sponsored')->default(0)->after('published_at');
                }
            });
        } else {
            // どちらのテーブルも無い場合は何もしない（ログだけ）
            info('[migration] neither articles nor posts table exists; skipped adding is_sponsored');
        }
    }

    public function down(): void
    {
        // SQLite 環境では dropColumn が失敗することがあるため存在チェックしてから
        if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'is_sponsored')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('is_sponsored');
            });
        }
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'is_sponsored')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn('is_sponsored');
            });
        }
    }
};
