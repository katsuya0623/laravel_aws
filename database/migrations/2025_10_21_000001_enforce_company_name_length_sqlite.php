<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 既に同名のトリガーがあれば先に削除（再実行対策）
        DB::unprepared('DROP TRIGGER IF EXISTS trg_companies_name_length_insert;');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_companies_name_length_update;');

        // INSERT 時のチェック（31文字以上なら保存を中断）
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_companies_name_length_insert
BEFORE INSERT ON companies
FOR EACH ROW
WHEN length(NEW.name) > 30
BEGIN
    SELECT RAISE(ABORT, '企業名は30文字以内で入力してください。');
END;
SQL);

        // UPDATE 時のチェック（31文字以上なら保存を中断）
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_companies_name_length_update
BEFORE UPDATE ON companies
FOR EACH ROW
WHEN length(NEW.name) > 30
BEGIN
    SELECT RAISE(ABORT, '企業名は30文字以内で入力してください。');
END;
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_companies_name_length_insert;');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_companies_name_length_update;');
    }
};
