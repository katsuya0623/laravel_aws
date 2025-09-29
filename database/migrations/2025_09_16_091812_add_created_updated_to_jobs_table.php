<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('recruit_jobs', function (Blueprint $t) {
      if (!Schema::hasColumn('recruit_jobs','created_at')) $t->timestamp('created_at')->nullable();
      if (!Schema::hasColumn('recruit_jobs','updated_at')) $t->timestamp('updated_at')->nullable();
    });
  }
  public function down(): void {
    Schema::table('recruit_jobs', function (Blueprint $t) {
      if (Schema::hasColumn('recruit_jobs','created_at')) $t->dropColumn('created_at');
      if (Schema::hasColumn('recruit_jobs','updated_at')) $t->dropColumn('updated_at');
    });
  }
};
