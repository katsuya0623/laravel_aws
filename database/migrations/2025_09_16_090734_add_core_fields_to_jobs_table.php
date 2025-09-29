<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('recruit_jobs', function (Blueprint $t) {
      if (!Schema::hasColumn('recruit_jobs','title'))         $t->string('title')->after('id');
      if (!Schema::hasColumn('recruit_jobs','excerpt'))       $t->text('excerpt')->nullable();
      if (!Schema::hasColumn('recruit_jobs','slug'))          $t->string('slug')->nullable()->index();
      if (!Schema::hasColumn('recruit_jobs','status'))        $t->string('status',50)->nullable()->index();
      if (!Schema::hasColumn('recruit_jobs','published_at'))  $t->timestamp('published_at')->nullable()->index();
      if (!Schema::hasColumn('recruit_jobs','company_id'))    $t->unsignedBigInteger('company_id')->nullable()->index();
      if (!Schema::hasColumn('recruit_jobs','image_url'))     $t->string('image_url',2048)->nullable();
    });
  }
  public function down(): void {
    Schema::table('recruit_jobs', function (Blueprint $t) {
      foreach (['title','excerpt','slug','status','published_at','company_id','image_url'] as $c) {
        if (Schema::hasColumn('recruit_jobs',$c)) $t->dropColumn($c);
      }
    });
  }
};
