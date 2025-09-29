<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // 存在しない場合のみ追加
            if (!Schema::hasColumn('applications', 'status')) {
                $table->string('status', 20)->default('pending')->index()->after('job_id');
            }
            if (!Schema::hasColumn('applications', 'note')) {
                $table->text('note')->nullable()->after('status');
            }
        });

        // 既存行のNULLをpendingに補正（DB種別によりdefaultが既存に効かない場合がある）
        if (Schema::hasColumn('applications', 'status')) {
            DB::table('applications')->whereNull('status')->update(['status' => 'pending']);
        }
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('applications', 'status')) {
                // index 名を個別に指定していないのでカラムdropでindexも落ちます
                $table->dropColumn('status');
            }
        });
    }
};
