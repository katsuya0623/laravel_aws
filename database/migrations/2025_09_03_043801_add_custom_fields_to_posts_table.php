<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('excerpt', 200)->nullable()->after('title');
            $table->string('seo_title', 70)->nullable()->after('slug');
            $table->string('seo_description', 160)->nullable()->after('seo_title');
            $table->boolean('is_featured')->default(false)->after('seo_description');
            $table->unsignedSmallInteger('reading_time')->nullable()->after('is_featured');
        });
    }
    public function down(): void {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['excerpt','seo_title','seo_description','is_featured','reading_time']);
        });
    }
};
