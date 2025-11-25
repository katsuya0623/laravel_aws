<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // すでに user_id がある場合は何もしない
            if (! Schema::hasColumn('applications', 'user_id')) {
                $table->unsignedBigInteger('user_id')
                    ->nullable()
                    ->after('job_id');

                // users.id への外部キー（ダメならコメントアウトでもOK）
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'user_id')) {
                // 外部キーがあれば先に削除（無かったら catch で無視）
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn('user_id');
            }
        });
    }
};
