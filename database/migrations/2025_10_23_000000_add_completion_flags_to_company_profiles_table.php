<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('company_profiles', 'is_completed')) {
                $table->boolean('is_completed')->default(false)->after('address2')->index();
            }
            if (!Schema::hasColumn('company_profiles', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('is_completed')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_completed', 'completed_at']);
        });
    }
};
