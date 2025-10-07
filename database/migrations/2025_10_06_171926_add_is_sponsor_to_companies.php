<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'is_sponsor')) {
                $table->boolean('is_sponsor')->default(false)->after('name');
            }
        });
    }

    public function down(): void {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'is_sponsor')) {
                $table->dropColumn('is_sponsor');
            }
        });
    }
};
