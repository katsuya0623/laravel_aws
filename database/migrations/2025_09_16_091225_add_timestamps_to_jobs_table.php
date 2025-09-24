<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('jobs', function (Blueprint $t) {
      $hasCreated = Schema::hasColumn('jobs','created_at');
      $hasUpdated = Schema::hasColumn('jobs','updated_at');

      if (!$hasCreated && !$hasUpdated) {
        // 両方ないならまとめて追加
        $t->timestamps(); // nullableではないDBでもOK、Laravelが扱えます
      } else {
        if (!$hasCreated) $t->timestamp('created_at')->nullable();
        if (!$hasUpdated) $t->timestamp('updated_at')->nullable();
      }
    });
  }

  public function down(): void {
    Schema::table('jobs', function (Blueprint $t) {
      if (Schema::hasColumn('jobs','created_at')) $t->dropColumn('created_at');
      if (Schema::hasColumn('jobs','updated_at')) $t->dropColumn('updated_at');
    });
  }
};
