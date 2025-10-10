<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'description')) {
            Schema::table('companies', function (Blueprint $table) {
                // SQLite 互換のため after() は付けない
                $table->text('description')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'description')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
