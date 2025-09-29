<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLiteでも有効なテーブル名変更
        if (Schema::hasTable('recruit_jobs') && !Schema::hasTable('recruit_jobs')) {
            Schema::rename('recruit_jobs', 'recruit_jobs');
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('recruit_jobs') && !Schema::hasTable('recruit_jobs')) {
            Schema::rename('recruit_jobs', 'recruit_jobs');
        }
    }
};
