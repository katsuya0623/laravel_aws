<?php

// database/migrations/XXXX_XX_XX_XXXXXX_add_user_id_to_company_profiles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('company_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('company_profiles', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('company_id');
                $table->index('user_id');
            }
        });
    }
    public function down(): void {
        Schema::table('company_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('company_profiles', 'user_id')) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
