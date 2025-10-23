<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');                     // 招待先メール
            $table->string('company_name');              // 表示用企業名（受諾後にCompanyへ反映）
            $table->foreignId('company_id')              // 先にCompanyを作る設計
                  ->nullable()
                  ->constrained('companies')
                  ->nullOnDelete();
            $table->uuid('token')->unique();             // ワンタイムトークン
            $table->timestamp('expires_at');             // 有効期限
            $table->string('status')->default('pending');// pending / accepted / cancelled / expired
            $table->foreignId('invited_by')->nullable(); // 管理者IDなど任意
            $table->timestamps();

            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_invitations');
    }
};
