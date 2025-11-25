<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // 既にある環境向けにガードを入れておく
            if (! Schema::hasColumn('profiles', 'birthday')) {
                // display_name の後ろあたりに追加（位置はそこまで厳密じゃなくてOK）
                $table->date('birthday')->nullable()->after('display_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'birthday')) {
                $table->dropColumn('birthday');
            }
        });
    }
};
