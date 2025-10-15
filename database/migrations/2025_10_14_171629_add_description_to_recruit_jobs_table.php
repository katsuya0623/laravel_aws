<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('recruit_jobs', function (Blueprint $table) {
            $table->longText('description')->nullable()->after('work_style');
        });
    }

    public function down(): void {
        Schema::table('recruit_jobs', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
