<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Company;
use App\Models\Job;
use App\Models\CompanyProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index()
    {
        // —— 記事（公開済みだけ・最新6件）
        $latest = Post::query()
            ->when(Schema::hasColumn('posts','published_at'), function ($q) {
                $q->whereNotNull('published_at')
                  ->where('published_at','<=', now());
            })
            ->latest('published_at')
            ->latest('id')
            ->take(6)
            ->get();

        // —— 企業（TOP 表示ぶんだけ事前取得）
        $companiesTop = $this->topCompanies();

        // —— 求人（TOP 表示ぶんだけ）
        $jobsTop = Job::withoutGlobalScopes()
            ->select(['id','title','slug'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('front.home', compact('latest', 'companiesTop', 'jobsTop'));
    }

    /** TOP 用の企業リスト（デモ除外＆公開のみ。列存在チェックつき） */
    private function topCompanies()
    {
        try {
            $conn   = (new Company())->getConnectionName() ?: config('database.default');
            $schema = Schema::connection($conn);
            $q      = DB::connection($conn)->table('companies');

            // 公開フラグ
            if ($schema->hasColumn('companies','is_published')) {
                $q->where('is_published', 1);
            }
            // 論理削除
            if ($schema->hasColumn('companies','deleted_at')) {
                $q->whereNull('deleted_at');
            }
            // デモ除外
            if ($schema->hasColumn('companies','name')) {
                $q->where(function ($w) {
                    $w->whereNull('name')
                      ->orWhere(function ($x) {
                          $x->where('name','not like','%デモ%')
                            ->where('name','not like','%demo%');
                      });
                });
            }
            if ($schema->hasColumn('companies','slug')) {
                $q->where(function ($w) {
                    $w->whereNull('slug')
                      ->orWhere('slug','not like','%demo%');
                });
            }

            // ★追記: company_profiles と突合して「完了企業のみ」
            if ($schema->hasTable('company_profiles')
                && $schema->hasColumn('company_profiles','is_completed')
                && $schema->hasColumn('companies','name')) {

                $cpNameCol = $schema->hasColumn('company_profiles','company_name')
                    ? 'company_name'
                    : ($schema->hasColumn('company_profiles','name') ? 'name' : null);

                if ($cpNameCol) {
                    $q->join('company_profiles as cp', "cp.$cpNameCol", '=', 'companies.name')
                      ->where('cp.is_completed', 1);
                }
            }
            // ★追記ここまで

            // 使いそうな列だけ選択（存在チェック）
            $cols = [];
            foreach ([
                'id','name','slug','logo_path','logo','thumbnail_path',
                'location','updated_at','published_at'
            ] as $c) {
                if ($schema->hasColumn('companies',$c)) $cols[] = $c;
            }
            $q->select($cols ?: ['*']);

            // 並び
            if ($schema->hasColumn('companies','published_at')) $q->orderByDesc('published_at');
            if ($schema->hasColumn('companies','updated_at'))   $q->orderByDesc('updated_at');
            if ($schema->hasColumn('companies','id'))           $q->orderByDesc('id');

            // 取得 → ロゴURLと詳細キーを付与
            return collect($q->limit(5)->get())->map(function ($row) use ($schema) {
                $arr = (array) $row;
                $arr['logoUrl'] = $this->resolveLogoUrl($schema, $arr);
                $arr['showKey'] = $arr['slug'] ?? ($arr['id'] ?? null);
                return (object) $arr;
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * ロゴURLを解決
     * - companies 側: logo_path / logo / thumbnail_path を優先
     * - 無ければ company_profiles.company_name 突合 → logo_path
     * - /storage or public 直下も解決、無ければ noimage.svg
     */
    private function resolveLogoUrl($schema, array $row): string
    {
        $path = null;

        foreach (['logo_path','logo','thumbnail_path'] as $c) {
            if ($schema->hasColumn('companies', $c) && !empty($row[$c] ?? null)) {
                $path = $row[$c];
                break;
            }
        }

        if (!$path && !empty($row['name'])) {
            $profile = CompanyProfile::where('company_name', $row['name'])->first();
            if ($profile && !empty($profile->logo_path)) {
                $path = $profile->logo_path;
            }
        }

        if ($path) {
            if (preg_match('#^https?://#', $path)) return $path; // 既にフルURL
            if (Storage::disk('public')->exists($path)) return Storage::disk('public')->url($path); // /storage/...
            if (file_exists(public_path($path))) return asset($path); // public直下
        }

        return asset('images/noimage.svg');
    }
}
