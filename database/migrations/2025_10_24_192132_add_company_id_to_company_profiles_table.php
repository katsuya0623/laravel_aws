<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('company_profiles', 'company_id')) {
                // まずは nullable で追加（既存データがあっても安全）
                $table->foreignId('company_id')
                      ->nullable()
                      ->constrained()
                      ->cascadeOnDelete()
                      ->after('id');
            }
        });

        // （任意）ここで既存レコードの company_id を埋めたい場合の例
        // - 会社名が一致する場合をマッピング（SQLite なので PHP でループ）
        /*
        $profiles = DB::table('company_profiles')->whereNull('company_id')->get();
        foreach ($profiles as $p) {
            $companyId = DB::table('companies')
                ->where('name', $p->company_name) // 会社名でマッチさせる。他の手掛かりがあれば差し替えOK
                ->value('id');

            if ($companyId) {
                DB::table('company_profiles')
                    ->where('id', $p->id)
                    ->update(['company_id' => $companyId]);
            }
        }
        */

        // （任意）ユニーク制約を付けたい場合は、**全レコードが埋まってから** 別マイグレーションで追加推奨
        // Schema::table('company_profiles', function (Blueprint $table) {
        //     $table->unique('company_id');
        // });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('company_profiles', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};
