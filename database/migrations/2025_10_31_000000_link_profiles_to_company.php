<?php
// database/migrations/2025_10_31_000000_link_profiles_to_company.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('company_profiles')) return;

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // =======================
            // SQLite: 再作成方式で対応
            // =======================
            DB::beginTransaction();
            DB::statement('PRAGMA foreign_keys = OFF');

            // 失敗残骸の掃除（存在してもOK）
            try { DB::statement('DROP TABLE IF EXISTS company_profiles_tmp'); } catch (\Throwable $e) {}

            // 1) 最終構造の一時テーブル（★ここでは UNIQUE を張らない）
            Schema::create('company_profiles_tmp', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('company_id'); // NOT NULL 想定（後段でNULL行は入れない）

                // 互換用（元に無ければ後で NULL が入る）
                $t->string('company_name')->nullable();
                $t->string('name')->nullable();
                $t->string('slug')->nullable();
                $t->string('logo_path')->nullable();
                $t->string('kana')->nullable();
                $t->text('intro')->nullable();
                $t->text('description')->nullable();
                $t->string('website_url')->nullable();
                $t->string('email')->nullable();
                $t->string('phone')->nullable();
                $t->string('postal_code')->nullable();
                $t->string('prefecture')->nullable();
                $t->string('city')->nullable();
                $t->string('address1')->nullable();
                $t->string('address2')->nullable();
                $t->string('industry')->nullable();
                $t->integer('employees_count')->nullable();
                $t->date('founded_at')->nullable();

                // ★ NULL を避けるため default(false)
                $t->boolean('is_completed')->default(false);

                $t->timestamps();

                // FK は定義だけ作成（差し替え後に有効になる）
                $t->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            });

            // 2) 元テーブルの実在カラムを取得し、動的に INSERT 文を組み立て
            $candidateCols = [
                'company_name','name','slug','logo_path','kana','intro','description',
                'website_url','email','phone','postal_code','prefecture','city',
                'address1','address2','industry','employees_count','founded_at',
                // is_completed は後で個別に（NULL禁止にするため）
                'created_at','updated_at',
            ];

            $existingCols = collect(DB::select("PRAGMA table_info('company_profiles')"))
                ->pluck('name')
                ->all();

            // 挿入先カラム（tmp側）
            $targetCols = array_merge([
                'id','company_id','company_name','name','slug','logo_path','kana','intro','description',
                'website_url','email','phone','postal_code','prefecture','city','address1','address2',
                'industry','employees_count','founded_at','is_completed','created_at','updated_at'
            ]);
            $targetColsSql = implode(', ', $targetCols);

            // SELECT 句の組立
            $pieces = [];

            // id
            $pieces[] = 'cp.id';

            // company_id: companies.name と COALESCE(company_name, name)
            $coLeft  = in_array('company_name', $existingCols) ? 'cp.company_name' : 'NULL';
            $coRight = in_array('name',         $existingCols) ? 'cp.name'         : 'NULL';
            $pieces[] = "(SELECT c.id FROM companies c WHERE c.name = COALESCE($coLeft, $coRight) LIMIT 1) AS company_id";

            // 汎用列（存在しない列は NULL で埋める）
            foreach ([
                'company_name','name','slug','logo_path','kana','intro','description',
                'website_url','email','phone','postal_code','prefecture','city',
                'address1','address2','industry','employees_count','founded_at'
            ] as $col) {
                $pieces[] = (in_array($col, $existingCols) ? "cp.$col" : "NULL") . " AS $col";
            }

            // ★ is_completed は必ず 0/1 に（NULL禁止）
            if (in_array('is_completed', $existingCols)) {
                $pieces[] = "COALESCE(cp.is_completed, 0) AS is_completed";
            } else {
                $pieces[] = "0 AS is_completed";
            }

            // created_at / updated_at
            foreach (['created_at','updated_at'] as $col) {
                $pieces[] = (in_array($col, $existingCols) ? "cp.$col" : "NULL") . " AS $col";
            }

            $selectSql = implode(",\n                    ", $pieces);

            // company_id が解決できた行のみ取り込み（NOT NULL と UNIQUE の前提を満たす）
            DB::statement("
                INSERT INTO company_profiles_tmp ($targetColsSql)
                SELECT
                    $selectSql
                  FROM company_profiles cp
                 WHERE EXISTS (
                    SELECT 1 FROM companies c
                    WHERE c.name = COALESCE($coLeft, $coRight)
                 )
                 ORDER BY cp.updated_at DESC, cp.id DESC
            ");

            // 3) 重複 company_id を最新（updated_at → id）で残す
            $dupes = DB::table('company_profiles_tmp')
                ->select('company_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('company_id')
                ->having('cnt', '>', 1)
                ->pluck('company_id');

            foreach ($dupes as $cid) {
                $keepId = DB::table('company_profiles_tmp')
                    ->where('company_id', $cid)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->value('id');

                DB::table('company_profiles_tmp')
                    ->where('company_id', $cid)
                    ->where('id', '!=', $keepId)
                    ->delete();
            }

            // 4) ★ ここで UNIQUE を追加（挿入と重複削除の後）
            Schema::table('company_profiles_tmp', function (Blueprint $t) {
                $t->unique('company_id');
            });

            // 5) 差し替え
            Schema::drop('company_profiles');
            Schema::rename('company_profiles_tmp', 'company_profiles');

            DB::statement('PRAGMA foreign_keys = ON');
            DB::commit();
        } else {
            // =======================
            // MySQL 等: ALTER 方式
            // =======================
            DB::beginTransaction();

            // 1) company_id 追加
            Schema::table('company_profiles', function (Blueprint $t) {
                if (!Schema::hasColumn('company_profiles', 'company_id')) {
                    $t->unsignedBigInteger('company_id')->nullable()->after('id');
                }
            });

            // 2) データ突合（存在する列のみ使用）
            if (Schema::hasColumn('company_profiles','company_name')) {
                DB::statement("
                    UPDATE company_profiles
                       SET company_id = (
                           SELECT id FROM companies WHERE companies.name = company_profiles.company_name
                       )
                     WHERE company_id IS NULL
                ");
            }
            if (Schema::hasColumn('company_profiles','name')) {
                DB::statement("
                    UPDATE company_profiles
                       SET company_id = COALESCE(
                           company_id,
                           (SELECT id FROM companies WHERE companies.name = company_profiles.name)
                       )
                     WHERE company_id IS NULL
                ");
            }

            // 3) 重複 company_id を最新で残す
            $dupes = DB::table('company_profiles')
                ->select('company_id', DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('company_id')
                ->groupBy('company_id')
                ->having('cnt', '>', 1)
                ->pluck('company_id');

            foreach ($dupes as $cid) {
                $keepId = DB::table('company_profiles')
                    ->where('company_id', $cid)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->value('id');

                DB::table('company_profiles')
                    ->where('company_id', $cid)
                    ->where('id', '!=', $keepId)
                    ->delete();
            }

            // 4) NOT NULL + UNIQUE + FK
            DB::statement("ALTER TABLE company_profiles MODIFY company_id BIGINT UNSIGNED NOT NULL");

            Schema::table('company_profiles', function (Blueprint $t) {
                // 既にある場合は自動でスキップされる（Laravelの挙動に依存）
                $t->unique('company_id');
                $t->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            });

            DB::commit();
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('company_profiles')) return;

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // =======================
            // SQLite: 会社IDを除いた形に戻す
            // =======================
            DB::beginTransaction();
            DB::statement('PRAGMA foreign_keys = OFF');

            // 失敗残骸の掃除
            try { DB::statement('DROP TABLE IF EXISTS company_profiles_tmp'); } catch (\Throwable $e) {}

            // 1) 元構造（一例）を作成（company_id なし）
            Schema::create('company_profiles_tmp', function (Blueprint $t) {
                $t->id();
                $t->string('company_name')->nullable();
                $t->string('name')->nullable();
                $t->string('slug')->nullable();
                $t->string('logo_path')->nullable();
                $t->string('kana')->nullable();
                $t->text('intro')->nullable();
                $t->text('description')->nullable();
                $t->string('website_url')->nullable();
                $t->string('email')->nullable();
                $t->string('phone')->nullable();
                $t->string('postal_code')->nullable();
                $t->string('prefecture')->nullable();
                $t->string('city')->nullable();
                $t->string('address1')->nullable();
                $t->string('address2')->nullable();
                $t->string('industry')->nullable();
                $t->integer('employees_count')->nullable();
                $t->date('founded_at')->nullable();
                $t->boolean('is_completed')->default(false);
                $t->timestamps();
            });

            // 2) データ移行（company_id は捨てる）
            DB::statement("
                INSERT INTO company_profiles_tmp (
                    id, company_name, name, slug, logo_path, kana, intro, description,
                    website_url, email, phone, postal_code, prefecture, city, address1, address2,
                    industry, employees_count, founded_at, is_completed, created_at, updated_at
                )
                SELECT
                    id, company_name, name, slug, logo_path, kana, intro, description,
                    website_url, email, phone, postal_code, prefecture, city, address1, address2,
                    industry, employees_count, founded_at, is_completed, created_at, updated_at
                FROM company_profiles
            ");

            // 3) 差し替え
            Schema::drop('company_profiles');
            Schema::rename('company_profiles_tmp', 'company_profiles');

            DB::statement('PRAGMA foreign_keys = ON');
            DB::commit();
        } else {
            // =======================
            // MySQL 等: ALTER で戻す
            // =======================
            DB::beginTransaction();
            try { Schema::table('company_profiles', fn (Blueprint $t) => $t->dropForeign(['company_id'])); } catch (\Throwable $e) {}
            try { Schema::table('company_profiles', fn (Blueprint $t) => $t->dropUnique(['company_id'])); } catch (\Throwable $e) {}
            try { Schema::table('company_profiles', fn (Blueprint $t) => $t->dropColumn('company_id')); } catch (\Throwable $e) {}
            DB::commit();
        }
    }
};
