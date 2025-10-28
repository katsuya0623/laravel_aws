<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");

        $hasCol = fn(string $t, string $c) => Schema::hasTable($t) && Schema::hasColumn($t, $c);

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off');

            // ★ 前回失敗時の残骸を確実に掃除
            Schema::dropIfExists('company_profiles_tmp');

            // 既存カラム名一覧
            $nowCols = Schema::hasTable('company_profiles')
                ? collect(DB::select("PRAGMA table_info('company_profiles')"))->pluck('name')->all()
                : [];

            Schema::create('company_profiles_tmp', function (Blueprint $table) {
                $table->id();

                // 追加：company_id（nullable）
                $table->unsignedBigInteger('company_id')->nullable()->index();

                // 重要：user_id は nullable
                $table->unsignedBigInteger('user_id')->nullable()->index();

                $table->string('company_name')->nullable();
                $table->string('company_name_kana')->nullable();
                $table->text('description')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('website_url')->nullable();
                $table->string('email')->nullable();
                $table->string('tel')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('prefecture')->nullable();
                $table->string('city')->nullable();
                $table->string('address1')->nullable();
                $table->string('address2')->nullable();
                $table->string('industry')->nullable();
                $table->string('employees')->nullable();
                $table->date('founded_on')->nullable();

                $table->timestamps();

                // FK（SQLite でも一応定義）
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });

            if (Schema::hasTable('company_profiles')) {
                // 移行SQL（存在しない列はNULL、user_id=0 は NULL に正規化）
                $sel = [
                    in_array('id', $nowCols, true) ? 'id' : 'NULL AS id',
                    'NULL AS company_id',
                    in_array('user_id', $nowCols, true) ? 'NULLIF(user_id,0) AS user_id' : 'NULL AS user_id',
                    in_array('company_name', $nowCols, true) ? 'company_name' : 'NULL AS company_name',
                    in_array('company_name_kana', $nowCols, true) ? 'company_name_kana' : 'NULL AS company_name_kana',
                    in_array('description', $nowCols, true) ? 'description' : 'NULL AS description',
                    in_array('logo_path', $nowCols, true) ? 'logo_path' : 'NULL AS logo_path',
                    in_array('website_url', $nowCols, true) ? 'website_url' : 'NULL AS website_url',
                    in_array('email', $nowCols, true) ? 'email' : 'NULL AS email',
                    in_array('tel', $nowCols, true) ? 'tel' : 'NULL AS tel',
                    in_array('postal_code', $nowCols, true) ? 'postal_code' : 'NULL AS postal_code',
                    in_array('prefecture', $nowCols, true) ? 'prefecture' : 'NULL AS prefecture',
                    in_array('city', $nowCols, true) ? 'city' : 'NULL AS city',
                    in_array('address1', $nowCols, true) ? 'address1' : 'NULL AS address1',
                    in_array('address2', $nowCols, true) ? 'address2' : 'NULL AS address2',
                    in_array('industry', $nowCols, true) ? 'industry' : 'NULL AS industry',
                    in_array('employees', $nowCols, true) ? 'employees' : 'NULL AS employees',
                    in_array('founded_on', $nowCols, true) ? 'founded_on' : 'NULL AS founded_on',
                    in_array('created_at', $nowCols, true) ? 'created_at' : 'NULL AS created_at',
                    in_array('updated_at', $nowCols, true) ? 'updated_at' : 'NULL AS updated_at',
                ];

                DB::statement("
                    INSERT INTO company_profiles_tmp
                    (id, company_id, user_id, company_name, company_name_kana, description, logo_path, website_url, email, tel, postal_code, prefecture, city, address1, address2, industry, employees, founded_on, created_at, updated_at)
                    SELECT " . implode(', ', $sel) . " FROM company_profiles
                ");

                Schema::drop('company_profiles');
            }

            Schema::rename('company_profiles_tmp', 'company_profiles');
            DB::statement('PRAGMA foreign_keys=on');

            // 既存データの company_id を埋める必要があればここで UPDATE を追加
        } else {
            // MySQL / PostgreSQL
            Schema::table('company_profiles', function (Blueprint $table) use ($hasCol) {
                if (! $hasCol('company_profiles', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
                }

                // 既存FKを落としてから再定義
                try { $table->dropForeign(['company_id']); } catch (\Throwable $e) {}
                try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}

                // user_id を nullable に
                if ($hasCol('company_profiles', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                } else {
                    $table->unsignedBigInteger('user_id')->nullable()->after('company_id')->index();
                }

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off');

            // ★ 念のため先に掃除
            Schema::dropIfExists('company_profiles_tmp');

            // company_id を除去し、user_id NOT NULL に戻す構造に再作成
            Schema::create('company_profiles_tmp', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index(); // NOT NULL

                $table->string('company_name')->nullable();
                $table->string('company_name_kana')->nullable();
                $table->text('description')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('website_url')->nullable();
                $table->string('email')->nullable();
                $table->string('tel')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('prefecture')->nullable();
                $table->string('city')->nullable();
                $table->string('address1')->nullable();
                $table->string('address2')->nullable();
                $table->string('industry')->nullable();
                $table->string('employees')->nullable();
                $table->date('founded_on')->nullable();

                $table->timestamps();
            });

            // user_id が NULL の行は 0 で埋め戻す（ダウン時のみの暫定対応）
            DB::statement("
                INSERT INTO company_profiles_tmp
                (id, user_id, company_name, company_name_kana, description, logo_path, website_url, email, tel, postal_code, prefecture, city, address1, address2, industry, employees, founded_on, created_at, updated_at)
                SELECT id, IFNULL(user_id, 0), company_name, company_name_kana, description, logo_path, website_url, email, tel, postal_code, prefecture, city, address1, address2, industry, employees, founded_on, created_at, updated_at
                FROM company_profiles
            ");

            Schema::drop('company_profiles');
            Schema::rename('company_profiles_tmp', 'company_profiles');

            DB::statement('PRAGMA foreign_keys=on');
        } else {
            Schema::table('company_profiles', function (Blueprint $table) {
                try { $table->dropForeign(['company_id']); } catch (\Throwable $e) {}
                try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}

                if (Schema::hasColumn('company_profiles', 'company_id')) {
                    $table->dropColumn('company_id');
                }
                if (Schema::hasColumn('company_profiles', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable(false)->change();
                }
            });
        }
    }
};
