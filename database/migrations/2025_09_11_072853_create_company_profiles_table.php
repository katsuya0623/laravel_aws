<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');                  // 会社名
            $table->string('company_name_kana')->nullable(); // 会社名（カナ）
            $table->text('description')->nullable();         // 事業内容/紹介
            $table->string('logo_path')->nullable();         // ロゴ画像（storage）
            $table->string('website_url')->nullable();
            $table->string('email')->nullable();
            $table->string('tel')->nullable();
            // 住所
            $table->string('postal_code', 16)->nullable();
            $table->string('prefecture')->nullable();
            $table->string('city')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            // 任意のメタ
            $table->string('industry')->nullable();
            $table->unsignedInteger('employees')->nullable();
            $table->date('founded_on')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('company_profiles');
    }
};
