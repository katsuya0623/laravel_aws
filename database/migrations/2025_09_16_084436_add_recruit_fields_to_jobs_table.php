<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('jobs', function (Blueprint $t) {
            // 募集情報
            if (!Schema::hasColumn('jobs','location'))            $t->string('location')->nullable();
            if (!Schema::hasColumn('jobs','employment_type'))     $t->string('employment_type',50)->nullable(); // fulltime/parttime/contract/intern/other
            if (!Schema::hasColumn('jobs','work_style'))          $t->string('work_style',20)->nullable();      // onsite/hybrid/remote
            if (!Schema::hasColumn('jobs','openings'))            $t->unsignedInteger('openings')->nullable();

            // 給与
            if (!Schema::hasColumn('jobs','salary_min'))          $t->unsignedInteger('salary_min')->nullable();
            if (!Schema::hasColumn('jobs','salary_max'))          $t->unsignedInteger('salary_max')->nullable();
            if (!Schema::hasColumn('jobs','salary_currency'))     $t->string('salary_currency',3)->default('JPY');
            if (!Schema::hasColumn('jobs','salary_unit'))         $t->string('salary_unit',10)->default('month'); // year/month/hour
            if (!Schema::hasColumn('jobs','salary_notes'))        $t->string('salary_notes',255)->nullable();

            // スケジュール
            if (!Schema::hasColumn('jobs','application_deadline'))$t->date('application_deadline')->nullable();

            // 求める人物像/条件
            if (!Schema::hasColumn('jobs','experience_years_min'))$t->unsignedTinyInteger('experience_years_min')->nullable();
            if (!Schema::hasColumn('jobs','experience_years_max'))$t->unsignedTinyInteger('experience_years_max')->nullable();
            if (!Schema::hasColumn('jobs','education'))           $t->string('education')->nullable();
            if (!Schema::hasColumn('jobs','languages'))           $t->string('languages')->nullable();
            if (!Schema::hasColumn('jobs','visa_support'))        $t->boolean('visa_support')->nullable();
            if (!Schema::hasColumn('jobs','relocation_support'))  $t->boolean('relocation_support')->nullable();

            // 就業条件
            if (!Schema::hasColumn('jobs','work_hours'))          $t->string('work_hours')->nullable(); // 例: 10:00-19:00（休憩1h）
            if (!Schema::hasColumn('jobs','holidays'))            $t->string('holidays')->nullable();   // 例: 土日祝/夏季/年末年始

            // 詳細テキスト
            if (!Schema::hasColumn('jobs','selection_process'))   $t->text('selection_process')->nullable();
            if (!Schema::hasColumn('jobs','documents_required'))  $t->text('documents_required')->nullable();
            if (!Schema::hasColumn('jobs','responsibilities'))    $t->text('responsibilities')->nullable();
            if (!Schema::hasColumn('jobs','requirements'))        $t->text('requirements')->nullable();
            if (!Schema::hasColumn('jobs','benefits'))            $t->text('benefits')->nullable();

            // 補助情報
            if (!Schema::hasColumn('jobs','tech_stack'))          $t->text('tech_stack')->nullable();
            if (!Schema::hasColumn('jobs','tags'))                $t->string('tags')->nullable();                // カンマ区切り等
            if (!Schema::hasColumn('jobs','external_link_url'))   $t->string('external_link_url',2048)->nullable();
        });
    }

    public function down(): void {
        Schema::table('jobs', function (Blueprint $t) {
            foreach ([
                'location','employment_type','work_style','openings',
                'salary_min','salary_max','salary_currency','salary_unit','salary_notes',
                'application_deadline','experience_years_min','experience_years_max','education','languages',
                'visa_support','relocation_support','work_hours','holidays',
                'selection_process','documents_required','responsibilities','requirements','benefits',
                'tech_stack','tags','external_link_url'
            ] as $c) if (Schema::hasColumn('jobs',$c)) $t->dropColumn($c);
        });
    }
};
