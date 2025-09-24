<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::statement('PRAGMA foreign_keys = OFF');
        $rows = DB::table('applications')->get();

        DB::statement('CREATE TABLE applications_tmp (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NULL,
            message TEXT NULL,
            job_id INTEGER NOT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            FOREIGN KEY(job_id) REFERENCES job_posts(id) ON DELETE CASCADE
        )');

        foreach ($rows as $r) {
            if (DB::table('job_posts')->where('id',$r->job_id)->exists()) {
                DB::table('applications_tmp')->insert((array)$r);
            }
        }

        DB::statement('DROP TABLE applications');
        DB::statement('ALTER TABLE applications_tmp RENAME TO applications');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('CREATE TABLE applications_old AS SELECT * FROM applications');
        DB::statement('DROP TABLE applications');
        DB::statement('ALTER TABLE applications_old RENAME TO applications');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
