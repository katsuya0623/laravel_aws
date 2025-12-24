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
    /** 企業一覧（デモ除外 / 公開のみ / 完了企業のみ を SQLite でも確実に） */
    public function index(Request $request)
    {
        $conn   = (new Company())->getConnectionName() ?: config('database.default');
        $schema = Schema::connection($conn);

        $q = DB::connection($conn)->table('companies');

        // ★ 完了企業のみ
        $this->joinCompletedProfiles($schema, $q);

        // 公開のみ + デモ除外
        $this->applyPublicFilters($schema, $q);

        // 必要な列のみ（companies. を明示、エイリアスして元のキー名で返す）
        $this->selectBasicColumns($schema, $q);

        // 並び順（接頭辞つき）
        if ($schema->hasColumn('companies', 'updated_at')) $q->orderByDesc('companies.updated_at');
        if ($schema->hasColumn('companies', 'id'))         $q->orderByDesc('companies.id');

        // 取得 → 各行にロゴURLと詳細遷移キーを付与
        $companies = $q->get()->map(function ($row) use ($schema) {
            $arr = (array) $row;
            $arr['logoUrl'] = $this->resolveLogoUrl($schema, $arr);   // 一覧用ロゴ
            $arr['showKey'] = $arr['slug'] ?? ($arr['id'] ?? null);   // /company/{slugOrId}
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

        // ★ 完了企業のみ
        $this->joinCompletedProfiles($schema, $base);

        // 公開のみ + デモ除外
        $this->applyPublicFilters($schema, $base);

        // 必要な列のみ
        $this->selectBasicColumns($schema, $base);

        $row = null;

        // 数値なら 1) id → 2) slug で検索、文字列なら slug
        if (is_numeric($key)) {
            if ($schema->hasColumn('companies', 'id')) {
                $row = (clone $base)->where('companies.id', (int) $key)->first();
            }
            if (!$row && $schema->hasColumn('companies', 'slug')) {
                $row = (clone $base)->where('companies.slug', (string) $key)->first();
            }
        } else {
            if ($schema->hasColumn('companies', 'slug')) {
                $row = (clone $base)->where('companies.slug', $key)->first();
            }
        }

        abort_if(!$row, 404);

        $rowArr = (array) $row;

        // ✅ ここが重要：profile は company_id で取得（name一致はやめる）
        $profile = null;
        if ($schema->hasTable('company_profiles')) {
            if (!empty($rowArr['id']) && $schema->hasColumn('company_profiles', 'company_id')) {
                $profile = CompanyProfile::where('company_id', (int) $rowArr['id'])->first();
            } else {
                // 旧構造救済：company_id が無い環境だけ name で探す
                if (!empty($rowArr['name']) && $schema->hasColumn('company_profiles', 'company_name')) {
                    $profile = CompanyProfile::where('company_name', $rowArr['name'])->first();
                }
            }
        }

        // ロゴURLを確実に解決（profile も考慮）
        $logoUrl = $this->resolveLogoUrl($schema, $rowArr, $profile);

        // company オブジェクト化
        $company = (object) $rowArr;

        return view('front.company.show', [
            'company' => $company,
            'profile' => $profile,
            'logoUrl' => $logoUrl,
        ]);
    }

    /** ★ company_profiles と突合して完了企業だけに絞る */
    private function joinCompletedProfiles($schema, $q): void
    {
        if (!$schema->hasTable('company_profiles')) return;
        if (!$schema->hasColumn('company_profiles', 'is_completed')) return;

        // ✅ まず company_id join を優先
        if ($schema->hasColumn('company_profiles', 'company_id') && $schema->hasColumn('companies', 'id')) {
            $q->join('company_profiles as cp', 'cp.company_id', '=', 'companies.id')
              ->where('cp.is_completed', 1);
            return;
        }

        // 旧構造救済：name join
        if (!$schema->hasColumn('companies', 'name')) return;

        $cpNameCol = $schema->hasColumn('company_profiles', 'company_name')
            ? 'company_name'
            : ($schema->hasColumn('company_profiles', 'name') ? 'name' : null);

        if (!$cpNameCol) return;

        $q->join('company_profiles as cp', "cp.$cpNameCol", '=', 'companies.name')
          ->where('cp.is_completed', 1);
    }

    /** 公開のみ + デモ除外（＋削除除外）の条件を適用（companies. を明示） */
    private function applyPublicFilters($schema, $q): void
    {
        if ($schema->hasColumn('companies', 'is_published')) {
            $q->where('companies.is_published', 1);
        }
        if ($schema->hasColumn('companies', 'deleted_at')) {
            $q->whereNull('companies.deleted_at');
        }
        if ($schema->hasColumn('companies', 'name')) {
            $q->where(function ($w) {
                $w->whereNull('companies.name')
                  ->orWhere(function ($x) {
                      $x->where('companies.name', 'not like', '%デモ%')
                        ->where('companies.name', 'not like', '%demo%');
                  });
            });
        }
        if ($schema->hasColumn('companies', 'slug')) {
            $q->where(function ($w) {
                $w->whereNull('companies.slug')
                  ->orWhere('companies.slug', 'not like', '%demo%');
            });
        }
    }

    /** よく使う列だけを選択（companies. を明示、エイリアス付き） */
    private function selectBasicColumns($schema, $q): void
    {
        $cols = [];
        foreach (['id','name','slug','updated_at'] as $c) {
            if ($schema->hasColumn('companies', $c)) {
                $cols[] = DB::raw("companies.$c as $c");
            }
        }
        if (!$cols) $cols = ['companies.*'];
        $q->select($cols);
    }

    /**
     * 会社ロゴURLを解決する
     * - companies 側にロゴ系カラムがあれば優先
     * - 無ければ profile.logo_path を使用（company_id優先で取得済み）
     */
    private function resolveLogoUrl($schema, array $row, ?CompanyProfile $profile = null): string
    {
        $path = null;

        foreach (['logo_path', 'logo', 'thumbnail_path'] as $c) {
            if ($schema->hasColumn('companies', $c) && !empty($row[$c] ?? null)) {
                $path = $row[$c];
                break;
            }
        }

        if (!$path && $profile && !empty($profile->logo_path)) {
            $path = $profile->logo_path;
        }

        if ($path) {
            if (preg_match('#^https?://#', $path)) return $path;
            if (Storage::disk('public')->exists($path)) return Storage::disk('public')->url($path);
            if (file_exists(public_path($path))) return asset($path);
        }

        return asset('images/noimage.svg');
    }
}
