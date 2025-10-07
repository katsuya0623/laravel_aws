<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'sponsor_company_id')) {
                $table->foreignId('sponsor_company_id')
                    ->nullable()
                    ->constrained('companies')
                    ->nullOnDelete()
                    ->after('id');
            }
        });
    }

    public function down(): void {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'sponsor_company_id')) {
                $table->dropConstrainedForeignId('sponsor_company_id');
            }
        });
    }
};
