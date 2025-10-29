<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_histories', function (Blueprint $table) {
            $table->id();

            // ▼ SQLiteでも動きやすいようにFKはシンプル指定
            //    本番で厳密にするなら ->constrained('users')->cascadeOnDelete() もOK
            $table->unsignedBigInteger('user_id')->index();

            $table->string('company_name', 191);
            $table->string('position', 191)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable(); // null = 在職中
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['is_current', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_histories');
    }
};
