<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('articles')) {
            // articlesテーブルが無い環境ではスキップ
            return;
        }
        if (!Schema::hasColumn('articles', 'is_sponsored')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->boolean('is_sponsored')->default(0)->after('published_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'is_sponsored')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropColumn('is_sponsored');
            });
        }
    }
};
