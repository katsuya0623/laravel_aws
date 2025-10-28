<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillCompanyLinks extends Command
{
    protected $signature = 'fix:company-links {--dry-run}';
    protected $description = 'Backfill companies.user_id / company_user / company_profiles.user_id safely.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        if (! Schema::hasTable('companies')) {
            $this->error('companies table not found.');
            return self::FAILURE;
        }
        if (! Schema::hasTable('company_profiles')) {
            $this->error('company_profiles table not found.');
            return self::FAILURE;
        }

        $now = now();

        // 1) companies.user_id をできるだけ埋める
        if (Schema::hasColumn('companies', 'user_id')) {
            // a) 既に company_user があるならそれを優先
            if (Schema::hasTable('company_user')
                && Schema::hasColumn('company_user', 'company_id')
                && Schema::hasColumn('company_user', 'user_id')) {

                $rows = DB::table('companies')
                    ->leftJoin('company_user', 'company_user.company_id', '=', 'companies.id')
                    ->whereNull('companies.user_id')
                    ->whereNotNull('company_user.user_id')
                    ->select('companies.id as cid', 'company_user.user_id as uid')
                    ->get();

                foreach ($rows as $r) {
                    $this->line("companies.user_id <- company_user  [company_id={$r->cid}, user_id={$r->uid}]");
                    if (! $dry) {
                        DB::table('companies')->where('id', $r->cid)->update(['user_id' => $r->uid, 'updated_at' => $now]);
                    }
                }
            }

            // b) company_profiles.user_id があればフォールバック
            if (Schema::hasColumn('company_profiles', 'company_id') && Schema::hasColumn('company_profiles', 'user_id')) {
                $rows = DB::table('companies')
                    ->leftJoin('company_profiles', 'company_profiles.company_id', '=', 'companies.id')
                    ->whereNull('companies.user_id')
                    ->whereNotNull('company_profiles.user_id')
                    ->select('companies.id as cid', 'company_profiles.user_id as uid')
                    ->get();

                foreach ($rows as $r) {
                    $this->line("companies.user_id <- company_profiles [company_id={$r->cid}, user_id={$r->uid}]");
                    if (! $dry) {
                        DB::table('companies')->where('id', $r->cid)->update(['user_id' => $r->uid, 'updated_at' => $now]);
                    }
                }
            }
        }

        // 2) company_user の行を（無ければ）作る
        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'company_id')
            && Schema::hasColumn('company_user', 'user_id')
            && Schema::hasColumn('companies', 'user_id')) {

            $rows = DB::table('companies')
                ->whereNotNull('user_id')
                ->select('id as cid', 'user_id as uid')
                ->get();

            foreach ($rows as $r) {
                $exists = DB::table('company_user')
                    ->where('company_id', $r->cid)
                    ->where('user_id', $r->uid)
                    ->exists();
                if (! $exists) {
                    $this->line("insert company_user (company_id={$r->cid}, user_id={$r->uid})");
                    if (! $dry) {
                        DB::table('company_user')->insert([
                            'company_id' => $r->cid,
                            'user_id'    => $r->uid,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        // 3) company_profiles.user_id をできるだけ埋める
        if (Schema::hasColumn('company_profiles', 'user_id')) {
            // a) companies.user_id から
            if (Schema::hasColumn('company_profiles', 'company_id') && Schema::hasColumn('companies', 'user_id')) {
                $rows = DB::table('company_profiles')
                    ->leftJoin('companies', 'companies.id', '=', 'company_profiles.company_id')
                    ->whereNull('company_profiles.user_id')
                    ->whereNotNull('companies.user_id')
                    ->select('company_profiles.id as pid', 'companies.user_id as uid')
                    ->get();

                foreach ($rows as $r) {
                    $this->line("company_profiles.user_id <- companies.user_id [profile_id={$r->pid}, user_id={$r->uid}]");
                    if (! $dry) {
                        DB::table('company_profiles')->where('id', $r->pid)->update(['user_id' => $r->uid, 'updated_at' => $now]);
                    }
                }
            }

            // b) email 突合（profile.email → users.email）
            if (Schema::hasColumn('company_profiles', 'email') && Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
                $rows = DB::table('company_profiles')
                    ->leftJoin('users', 'users.email', '=', 'company_profiles.email')
                    ->whereNull('company_profiles.user_id')
                    ->whereNotNull('company_profiles.email')
                    ->select('company_profiles.id as pid', 'users.id as uid')
                    ->get();

                foreach ($rows as $r) {
                    if (! $r->uid) continue;
                    $this->line("company_profiles.user_id <- users.email [profile_id={$r->pid}, user_id={$r->uid}]");
                    if (! $dry) {
                        DB::table('company_profiles')->where('id', $r->pid)->update(['user_id' => $r->uid, 'updated_at' => $now]);
                    }
                }
            }
        }

        $this->info($dry ? 'Done (dry-run)' : 'Done');
        return self::SUCCESS;
    }
}
