<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name')->nullable();      // 表示名
            $table->text('bio')->nullable();                 // 自己紹介
            $table->string('avatar_path')->nullable();       // アイコン画像（storage）
            $table->string('website_url')->nullable();
            $table->string('x_url')->nullable();             // X(旧Twitter)
            $table->string('instagram_url')->nullable();
            $table->string('location')->nullable();
            $table->date('birthday')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('profiles');
    }
};
