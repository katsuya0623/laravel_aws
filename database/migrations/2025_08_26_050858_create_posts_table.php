<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('slug')->unique();
            $t->string('thumbnail_path')->nullable();
            $t->longText('body');
            $t->timestamp('published_at')->nullable();
            $t->enum('status', ['draft', 'published'])->default('draft');
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('posts');
    }
};
