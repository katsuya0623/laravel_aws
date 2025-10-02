<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('company_user')) {
            Schema::create('company_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['company_profile_id','user_id']);
            });
        }

        // 旧互換: company_profiles.user_id をピボットへバックフィル
        if (Schema::hasColumn('company_profiles','user_id')) {
            $pairs = DB::table('company_profiles')->whereNotNull('user_id')->get(['id','user_id']);
            foreach ($pairs as $p) {
                $exists = DB::table('company_user')
                    ->where('company_profile_id',$p->id)
                    ->where('user_id',$p->user_id)
                    ->exists();
                if (!$exists) {
                    DB::table('company_user')->insert([
                        'company_profile_id' => $p->id,
                        'user_id'            => $p->user_id,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
