<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // SQLite は FK 有効のまま ALTER できないので一時的に OFF
        DB::statement('PRAGMA foreign_keys = OFF');

        // 既存データ退避
        $rows = DB::table('applications')->get();

        // 正しい FK 定義で作り直し（job_id → jobs.id）
        Schema::create('applications_tmp', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
            $t->text('message')->nullable();
            $t->unsignedBigInteger('job_id');
            $t->timestamps();
            $t->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });

        // jobs に存在するものだけコピー
        foreach ($rows as $r) {
            if (DB::table('jobs')->where('id', $r->job_id)->exists()) {
                DB::table('applications_tmp')->insert([
                    'id'         => $r->id,
                    'name'       => $r->name,
                    'email'      => $r->email,
                    'phone'      => $r->phone,
                    'message'    => $r->message,
                    'job_id'     => $r->job_id,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ]);
            }
        }

        Schema::drop('applications');
        Schema::rename('applications_tmp', 'applications');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('applications_old', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
            $t->text('message')->nullable();
            $t->unsignedBigInteger('job_id');
            $t->timestamps();
        });

        DB::table('applications_old')->insert(
            DB::table('applications')->get()->map(fn($r)=>(array)$r)->toArray()
        );

        Schema::drop('applications');
        Schema::rename('applications_old', 'applications');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
