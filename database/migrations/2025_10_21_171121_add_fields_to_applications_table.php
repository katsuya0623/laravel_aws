<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $t) {
            // 既存: id, job_id, name, email, phone, message, timestamps
            $t->string('kana')->nullable()->after('name');                 // フリガナ
            $t->string('current_status')->nullable()->after('phone');      // 現在の状況
            $t->string('employment_type')->nullable()->after('current_status'); // 希望雇用形態
            $t->text('motivation')->nullable()->after('employment_type');  // 志望動機
            $t->text('pr')->nullable()->after('motivation');               // 自己PR / 自由記述
            $t->string('resume_path')->nullable()->after('pr');            // 添付ファイルパス
            $t->string('ip', 45)->nullable()->after('resume_path');        // 送信元IP
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $t) {
            $t->dropColumn([
                'kana','current_status','employment_type',
                'motivation','pr','resume_path','ip',
            ]);
        });
    }
};
