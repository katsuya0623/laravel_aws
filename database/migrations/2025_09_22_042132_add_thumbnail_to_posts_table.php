<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts','thumbnail')) {
                // storage/app/public/... に保存する相対パスを想定
                $table->string('thumbnail')->nullable()->after('title');
            }
        });
    }

    public function down(): void {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts','thumbnail')) {
                $table->dropColumn('thumbnail');
            }
        });
    }
};
