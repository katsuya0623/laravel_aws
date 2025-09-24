<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    if (!Schema::hasTable('job_posts')) {
      Schema::create('job_posts', function (Blueprint $t) {
        $t->id();
        // 基本
        $t->string('title');
        $t->text('excerpt')->nullable();
        $t->string('slug')->nullable()->index();
        $t->string('status',50)->nullable()->index();
        $t->timestamp('published_at')->nullable()->index();
        $t->unsignedBigInteger('company_id')->nullable()->index();
        $t->string('image_url',2048)->nullable();
        // 募集情報
        $t->string('location')->nullable();
        $t->string('employment_type',50)->nullable();       // fulltime/parttime/contract/intern/other
        $t->string('work_style',20)->nullable();            // onsite/hybrid/remote
        $t->integer('openings')->nullable();
        // 給与
        $t->integer('salary_min')->nullable();
        $t->integer('salary_max')->nullable();
        $t->string('salary_currency',3)->nullable();        // JPY 等
        $t->string('salary_unit',10)->nullable();           // year/month/hour
        $t->string('salary_notes',255)->nullable();
        // 条件
        $t->date('application_deadline')->nullable();
        $t->integer('experience_years_min')->nullable();
        $t->integer('experience_years_max')->nullable();
        $t->string('education')->nullable();
        $t->string('languages')->nullable();
        $t->boolean('visa_support')->default(false);
        $t->boolean('relocation_support')->default(false);
        $t->string('work_hours')->nullable();
        $t->string('holidays')->nullable();
        // 詳細
        $t->text('selection_process')->nullable();
        $t->text('documents_required')->nullable();
        $t->text('responsibilities')->nullable();
        $t->text('requirements')->nullable();
        $t->text('benefits')->nullable();
        // 補助
        $t->text('tech_stack')->nullable();
        $t->string('tags')->nullable();
        $t->string('external_link_url',2048)->nullable();

        $t->timestamps();
      });
    }
  }
  public function down(): void {
    Schema::dropIfExists('job_posts');
  }
};
