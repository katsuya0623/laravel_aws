<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL では既に recruit_jobs テーブルが存在し、
        // SQLite 用リビルドは不要なので何もしない。
    }

    public function down(): void
    {
        // up() が何もしないので、down() も何もしない。
    }
};
