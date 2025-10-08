<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // SQLite対策: まずは nullable で追加
            if (!Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable()->after('id');
                $table->unique('email');
            }
            if (!Schema::hasColumn('companies', 'password')) {
                $table->string('password')->nullable()->after('email');
            }

            // 無ければ name/slug も補完（nullable でOK）
            if (!Schema::hasColumn('companies', 'name')) {
                $table->string('name')->nullable()->after('password');
            }
            if (!Schema::hasColumn('companies', 'slug')) {
                $table->string('slug')->nullable()->after('name');
                $table->unique('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // ユニークインデックス削除（存在すれば）
            try { $table->dropUnique('companies_email_unique'); } catch (\Throwable $e) {}
            try { $table->dropUnique('companies_slug_unique'); } catch (\Throwable $e) {}

            foreach (['email','password','name','slug'] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
