<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $conn   = config('database.default');
        $driver = config("database.connections.$conn.driver");

        // applications が無ければ何もしない
        if (! Schema::hasTable('applications')) {
            return;
        }

        if ($driver === 'sqlite') {
            // 既存の tmp を掃除
            Schema::dropIfExists('applications_tmp');

            DB::beginTransaction();
            try {
                DB::statement('PRAGMA foreign_keys=OFF');

                // 一時テーブルを作成（参照先を recruit_jobs に修正）
                Schema::create('applications_tmp', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->string('email');
                    $table->string('phone')->nullable();
                    $table->text('message')->nullable();

                    $table->unsignedBigInteger('job_id');
                    $table->foreign('job_id')
                        ->references('id')->on('recruit_jobs')
                        ->cascadeOnDelete();

                    $table->timestamps();

                    // 必要ならインデックス等ここに再定義
                    // $table->index('job_id');
                });

                // データ移送（該当カラムのみ）
                DB::statement("
                    INSERT INTO applications_tmp (id, name, email, phone, message, job_id, created_at, updated_at)
                    SELECT id, name, email, phone, message, job_id, created_at, updated_at FROM applications
                ");

                Schema::drop('applications');
                Schema::rename('applications_tmp', 'applications');

                DB::statement('PRAGMA foreign_keys=ON');
                DB::commit();
            } catch (\Throwable $e) {
                // 失敗しても必ず ON に戻す
                try { DB::statement('PRAGMA foreign_keys=ON'); } catch (\Throwable $e2) {}
                DB::rollBack();
                throw $e;
            }
        } else {
            // MySQL/PostgreSQL 等の場合（参考：MySQL想定）
            Schema::table('applications', function (Blueprint $table) {
                // 既存FK名が不明な場合は手で落とす必要があります（例）
                // $table->dropForeign(['job_id']);
            });
            Schema::table('applications', function (Blueprint $table) {
                $table->foreign('job_id')
                    ->references('id')->on('recruit_jobs')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // 必要なら元の job_posts へ戻す処理を実装
        // （本番で戻す予定がなければ空でもOK）
    }
};
