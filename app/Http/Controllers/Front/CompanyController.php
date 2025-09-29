<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;
use App\Models\CompanyProfile;

class CompanyController extends Controller
{
    /** 企業一覧（デモ除外 / 公開のみ を SQLite でも確実に） */
    public function index(Request $request)
    {
        $conn   = (new Company())->getConnectionName() ?: config('database.default');
        $schema = Schema::connection($conn);

        $q = DB::connection($conn)->table('companies');

        // 公開のみ + デモ除外
        $this->applyPublicFilters($schema, $q);

        // 必要な列のみ
        $this->selectBasicColumns($schema, $q);

        // 並び順
        if ($schema->hasColumn('companies', 'updated_at')) $q->orderByDesc('updated_at');
        if ($schema->hasColumn('companies', 'id'))         $q->orderByDesc('id');

        // 取得 → 各行にロゴURLと詳細遷移キーを付与
        $companies = $q->get()->map(function ($row) use ($schema) {
            $arr = (array) $row;
            $arr['logoUrl'] = $this->resolveLogoUrl($schema, $arr);                       // 一覧用ロゴ
            $arr['showKey'] = $arr['slug'] ?? ($arr['id'] ?? null);                       // /company/{slugOrId}
            return (object) $arr;
        });

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

        $key  = urldecode($slugOrId);
        $base = DB::connection($conn)->table('companies');

        // 公開のみ + デモ除外
        $this->applyPublicFilters($schema, $base);

        // 必要な列のみ
        $this->selectBasicColumns($schema, $base);

        $row = null;

        // 数値なら 1) id → 2) slug で検索、文字列なら slug
        if (is_numeric($key)) {
            $row = (clone $base)->where('id', (int) $key)->first();
            if (!$row && $schema->hasColumn('companies', 'slug')) {
                $row = (clone $base)->where('slug', (string) $key)->first();
            }
        } else {
            if ($schema->hasColumn('companies', 'slug')) {
                $row = (clone $base)->where('slug', $key)->first();
            }
        }

        abort_if(!$row, 404);

        // ロゴURLを確実に解決
        $logoUrl = $this->resolveLogoUrl($schema, (array) $row);

        $company = (object) $row;

        return view('front.company.show', [
            'company' => $company,
            'logoUrl' => $logoUrl,   // Blade 側ではこれを <img src> に
        ]);
    }

    /** 公開のみ + デモ除外（＋削除除外）の条件を適用 */
    private function applyPublicFilters($schema, $q): void
    {
        if ($schema->hasColumn('companies', 'is_published')) {
            $q->where('is_published', 1);
        }
        if ($schema->hasColumn('companies', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
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

    /** よく使う列だけを選択（存在チェック付き） */
    private function selectBasicColumns($schema, $q): void
    {
        $cols = [];
        foreach (['id','name','slug','updated_at'] as $c) {
            if ($schema->hasColumn('companies', $c)) $cols[] = $c;
        }
        if (!$cols) $cols = ['*'];
        $q->select($cols);
    }

    /**
     * 会社ロゴURLを解決する
     * - companies 側にロゴ系カラムがあれば優先
     * - 無ければ CompanyProfile(company_name 一致) の logo_path を使用
     * - パスを URL に変換（/storage シンボリックリンク対応）
     */
    private function resolveLogoUrl($schema, array $row): string
    {
        $path = null;

        // 1) companies 側のよくあるロゴ列
        foreach (['logo_path', 'logo', 'thumbnail_path'] as $c) {
            if ($schema->hasColumn('companies', $c) && !empty($row[$c] ?? null)) {
                $path = $row[$c];
                break;
            }
        }

        // 2) 無ければ CompanyProfile を name で突合
        if (!$path && !empty($row['name'])) {
            $profile = CompanyProfile::where('company_name', $row['name'])->first();
            if ($profile && !empty($profile->logo_path)) {
                $path = $profile->logo_path;
            }
        }

        // 3) URL に解決
        if ($path) {
            if (preg_match('#^https?://#', $path)) {
                return $path;                // 既にフルURL
            }
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);  // /storage/...
            }
            if (file_exists(public_path($path))) {
                return asset($path);         // public直下
            }
        }

        // 4) フォールバック
        return asset('images/noimage.svg');
    }
}
