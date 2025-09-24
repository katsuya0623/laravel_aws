<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;

class CompanyController extends Controller
{
    /** 企業一覧（デモ除外 / 公開のみ を SQLite でも確実に） */
    public function index(Request $request)
    {
        $conn   = (new Company())->getConnectionName() ?: config('database.default');
        $schema = Schema::connection($conn);

        $q = DB::connection($conn)->table('companies');

        // 公開のみ + デモ除外を適用
        $this->applyPublicFilters($schema, $q);

        // 表示に使う列（存在するものだけ）
        $this->selectBasicColumns($schema, $q);

        // 並び順
        if ($schema->hasColumn('companies', 'updated_at')) $q->orderByDesc('updated_at');
        if ($schema->hasColumn('companies', 'id'))         $q->orderByDesc('id');

        $companies = $q->get();

        return view('front.company.index', [
            'companies' => $companies,
            'q'         => (string) $request->get('q', ''),
        ]);
    }

    /** 企業詳細（slug または id）— 一覧と同じ接続・同じ公開条件で */
    public function show(string $slugOrId)
    {
        $conn   = (new Company())->getConnectionName() ?: config('database.default');
        $schema = Schema::connection($conn);

        $key = urldecode($slugOrId);

        $base = DB::connection($conn)->table('companies');

        // 公開のみ + デモ除外を適用
        $this->applyPublicFilters($schema, $base);

        // 必要な列のみ
        $this->selectBasicColumns($schema, $base);

        $row = null;

        // 数値なら 1) id → 2) slug で検索
        if (is_numeric($key)) {
            $row = (clone $base)->where('id', (int)$key)->first();
            if (!$row && $schema->hasColumn('companies', 'slug')) {
                $row = (clone $base)->where('slug', (string)$key)->first();
            }
        } else {
            if ($schema->hasColumn('companies', 'slug')) {
                $row = (clone $base)->where('slug', $key)->first();
            }
        }

        abort_if(!$row, 404);

        $company = (object) $row;
        return view('front.company.show', compact('company'));
    }

    /** 一覧/詳細 共通: 公開のみ + デモ除外（＋削除除外）の条件を適用 */
    private function applyPublicFilters($schema, $q): void
    {
        // 公開フラグ（1 のみ）
        if ($schema->hasColumn('companies', 'is_published')) {
            $q->where('is_published', 1);
        }

        // 削除済みは除外（ソフトデリート対策）
        if ($schema->hasColumn('companies', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        // デモ除外（name に「デモ」/ slug に demo を含まない。NULL は通す）
        if ($schema->hasColumn('companies', 'name')) {
            $q->where(function ($w) {
                $w->whereNull('name')
                  ->orWhere(function ($x) {
                      $x->where('name', 'not like', '%デモ%')
                        ->where('name', 'not like', '%demo%');
                  });
            });
        }
        if ($schema->hasColumn('companies', 'slug')) {
            $q->where(function ($w) {
                $w->whereNull('slug')
                  ->orWhere('slug', 'not like', '%demo%');
            });
        }
    }

    /** 一覧/詳細 共通: よく使う列だけを選択（存在チェック付き） */
    private function selectBasicColumns($schema, $q): void
    {
        $cols = [];
        foreach (['id','name','slug','updated_at'] as $c) {
            if ($schema->hasColumn('companies', $c)) $cols[] = $c;
        }
        if (!$cols) $cols = ['*'];
        $q->select($cols);
    }
}
