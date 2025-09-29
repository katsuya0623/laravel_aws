<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('recruit_jobs')) return;

        // 1) 新テーブルを作成（不要な3列は作らない）
        Schema::create('recruit_jobs_new', function (Blueprint $t) {
            $t->increments('id');
            $t->string('title');
            $t->text('excerpt')->nullable();
            $t->string('slug')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->string('status', 50)->nullable();
            $t->unsignedInteger('company_id')->nullable();
            $t->string('image_url', 2048)->nullable();

            // 募集情報
            $t->string('location')->nullable();
            $t->string('employment_type', 50)->nullable();  // fulltime/parttime/contract/intern/other
            $t->string('work_style', 20)->nullable();       // onsite/hybrid/remote
            $t->unsignedInteger('openings')->nullable();

            // 給与
            $t->unsignedInteger('salary_min')->nullable();
            $t->unsignedInteger('salary_max')->nullable();
            $t->string('salary_currency', 3)->nullable();
            $t->string('salary_unit', 10)->nullable();      // year/month/hour
            $t->string('salary_notes', 255)->nullable();

            // 条件・スケジュール
            $t->date('application_deadline')->nullable();
            $t->unsignedInteger('experience_years_min')->nullable();
            $t->unsignedInteger('experience_years_max')->nullable();
            $t->string('education')->nullable();
            $t->string('languages')->nullable();
            $t->boolean('visa_support')->default(false);
            $t->boolean('relocation_support')->default(false);
            $t->string('work_hours')->nullable();
            $t->string('holidays')->nullable();

            // 詳細テキスト
            $t->text('selection_process')->nullable();
            $t->text('documents_required')->nullable();
            $t->text('responsibilities')->nullable();
            $t->text('requirements')->nullable();
            $t->text('benefits')->nullable();

            // 補助情報
            $t->text('tech_stack')->nullable();
            $t->string('tags', 255)->nullable();
            $t->string('external_link_url', 2048)->nullable();

            $t->timestamps(); // created_at, updated_at
        });

        // 2) 旧テーブルに存在し、新テーブルにもある共通列だけをコピー
        $existing = Schema::getColumnListing('recruit_jobs');   // 旧
        $target   = Schema::getColumnListing('recruit_jobs_new'); // 新
        $copyCols = array_values(array_intersect($existing, $target));
        if (!empty($copyCols)) {
            $cols = implode(', ', array_map(fn($c)=>"`$c`", $copyCols));
            DB::statement("INSERT INTO recruit_jobs_new ($cols) SELECT $cols FROM recruit_jobs");
        }

        // 3) 旧テーブルを削除し、新テーブルを正式名に
        Schema::drop('recruit_jobs');
        Schema::rename('recruit_jobs_new', 'recruit_jobs');
    }

    public function down(): void
    {
        // 元に戻す（queue/payload/attempts を復元）※簡易実装
        if (!Schema::hasTable('recruit_jobs')) return;

        Schema::create('recruit_jobs_oldshape', function (Blueprint $t) {
            $t->increments('id');
            $t->string('title');
            $t->text('excerpt')->nullable();
            $t->string('slug')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->string('status', 50)->nullable();
            $t->unsignedInteger('company_id')->nullable();
            $t->string('image_url', 2048)->nullable();
            $t->string('location')->nullable();
            $t->string('employment_type', 50)->nullable();
            $t->string('work_style', 20)->nullable();
            $t->unsignedInteger('openings')->nullable();
            $t->unsignedInteger('salary_min')->nullable();
            $t->unsignedInteger('salary_max')->nullable();
            $t->string('salary_currency', 3)->nullable();
            $t->string('salary_unit', 10)->nullable();
            $t->string('salary_notes', 255)->nullable();
            $t->date('application_deadline')->nullable();
            $t->unsignedInteger('experience_years_min')->nullable();
            $t->unsignedInteger('experience_years_max')->nullable();
            $t->string('education')->nullable();
            $t->string('languages')->nullable();
            $t->boolean('visa_support')->default(false);
            $t->boolean('relocation_support')->default(false);
            $t->string('work_hours')->nullable();
            $t->string('holidays')->nullable();
            $t->text('selection_process')->nullable();
            $t->text('documents_required')->nullable();
            $t->text('responsibilities')->nullable();
            $t->text('requirements')->nullable();
            $t->text('benefits')->nullable();
            $t->text('tech_stack')->nullable();
            $t->string('tags', 255)->nullable();
            $t->string('external_link_url', 2048)->nullable();

            // 復元列（NOT NULL）
            $t->string('queue')->default('');
            $t->text('payload');
            $t->unsignedTinyInteger('attempts')->default(0);

            $t->timestamps();
        });

        $existing = Schema::getColumnListing('recruit_jobs');
        $target   = Schema::getColumnListing('recruit_jobs_oldshape');
        $copyCols = array_values(array_intersect($existing, $target));
        if (!empty($copyCols)) {
            $cols = implode(', ', array_map(fn($c)=>"`$c`", $copyCols));
            DB::statement("INSERT INTO recruit_jobs_oldshape ($cols) SELECT $cols FROM recruit_jobs");
        }

        Schema::drop('recruit_jobs');
        Schema::rename('recruit_jobs_oldshape', 'recruit_jobs');
    }
};
