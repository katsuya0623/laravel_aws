<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Support\RoleResolver;
use App\Models\Job;
use App\Models\Company;

class FrontJobController extends Controller
{
    /** 求人一覧 */
    public function index(Request $request)
    {
        $q      = trim($request->input('q', ''));
        $status = $request->input('status', '');

        $table = (new Job)->getTable(); // 例: recruit_jobs

        // ★ 企業プロフィールが完了済みの企業のみ表示（スキーマに応じて安全にJOIN）
        $jobs = $this->joinCompletedCompanyProfile(Job::query()->with('company'), $table);

        // キーワード検索
        if ($q !== '') {
            foreach (preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) as $kw) {
                $jobs->where(function ($qq) use ($kw, $table) {
                    $like = "%{$kw}%";
                    $qq->where('title', 'like', $like)
                       ->orWhere('description', 'like', $like)
                       ->orWhere('location', 'like', $like)
                       ->orWhere('tags', 'like', $like);

                    if (Schema::hasColumn($table, 'company_name')) {
                        $qq->orWhere('company_name', 'like', $like);
                    }
                });
            }
        }

        // ステータス絞り込み
        $jobs->when(in_array($status, ['draft', 'published'], true), function ($qb) use ($status, $table) {
            if (Schema::hasColumn($table, 'status')) {
                $qb->where('status', $status);
            } elseif (Schema::hasColumn($table, 'is_published')) {
                $qb->where('is_published', $status === 'published' ? 1 : 0);
            }
        });

        $jobs = $jobs->latest("$table.id")
            ->paginate(20)
            ->withQueryString();

        return view('front.jobs.index', compact('jobs', 'q', 'status'));
    }

    /** 求人詳細（slug or id） */
    public function show(string $slugOrId)
    {
        $table = (new Job)->getTable();

        // ★ 企業プロフィールが完了済みの企業のみ表示（スキーマに応じて安全にJOIN）
        $jobQuery = $this->joinCompletedCompanyProfile(Job::query()->with('company'), $table);

        $job = $jobQuery
            ->when(
                is_numeric($slugOrId),
                fn($q) => $q->where("$table.id", $slugOrId),
                fn($q) => $q->where("$table.slug", $slugOrId)
            )
            ->firstOrFail();

        return view()->exists('front.jobs.show')
            ? view('front.jobs.show', compact('job'))
            : view('front.jobs.index', ['jobs' => collect([$job]), 'q' => null, 'status' => null]);
    }

    /** 作成フォーム（企業限定） */
    public function create()
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403);
        }

        [$ids, $companies] = $this->companiesFor($user->id);

        return view('front.jobs.create', [
            'companies' => $companies,
            'companyId' => count($ids) === 1 ? $ids[0] : null,
        ]);
    }

    /** 登録（企業限定） */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403);
        }

        [$allowedIds] = $this->companiesFor($user->id);
        if (empty($allowedIds)) {
            return back()->withErrors(['company_id' => '企業プロフィールで会社を設定してください。'])->withInput();
        }

        // ★ タグの空白を正規化（全角/連続スペース → 半角1つ）
        $request->merge([
            'tags' => preg_replace('/\s+/u', ' ', trim((string) $request->input('tags'))),
        ]);

        $this->normalizePayload($request);

        [$rules, $messages, $attributes] = $this->rules();
        // 画像ファイル
        $rules['image'] = ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        // ★ 自分が持つ会社のみ許可
        $rules['company_id'] = ['required', 'integer', Rule::in($allowedIds)];

        $data = $request->validate($rules, $messages, $attributes);

        // slug
        $slugBase = Str::slug($data['title']) ?: Str::lower(Str::random(8));
        $slug = $slugBase;
        $i = 1;
        while (Job::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . (++$i);
        }

        $table = (new Job)->getTable();

        $full = [
            'title'           => $data['title'],
            'description'     => $data['body'],
            'slug'            => $slug,
            'user_id'         => $user->id, // カラムが無ければ後段で弾かれるので安全
            'company_id'      => (int) $data['company_id'],
            'company_name'    => $data['company_name']    ?? null,
            'location'        => $data['location']        ?? null,
            'employment_type' => $data['employment_type'] ?? null,
            'work_style'      => $data['work_style']      ?? null,
            'salary_from'     => $data['salary_from']     ?? null,
            'salary_to'       => $data['salary_to']       ?? null,
            'salary_unit'     => $data['salary_unit']     ?? null,
            'apply_url'       => $data['apply_url']       ?? null,
            'external_url'    => $data['external_url']    ?? null,
            'tags'            => $data['tags']            ?? null,
        ];

        if (Schema::hasColumn($table, 'excerpt') && isset($data['summary'])) {
            $full['excerpt'] = $data['summary'];
        }
        if (Schema::hasColumn($table, 'status')) {
            $full['status'] = $data['status'];
        }
        if (Schema::hasColumn($table, 'is_published')) {
            $full['is_published'] = ($data['status'] ?? 'draft') === 'published' ? 1 : 0;
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('recruit_jobs', 'public');
            if (Schema::hasColumn($table, 'image_path')) {
                $full['image_path'] = $path;
            }
        }

        $cols    = Schema::getColumnListing($table);
        $payload = array_intersect_key($full, array_flip($cols));

        Job::create($payload);

        return redirect()->route('front.jobs.index')->with('success', '求人を作成しました。');
    }

    /** 更新（企業限定） */
    public function update(Request $request, Job $job)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403);
        }

        [$allowedIds] = $this->companiesFor($user->id);
        if (empty($allowedIds)) {
            return back()->withErrors(['company_id' => '企業プロフィールで会社を設定してください。'])->withInput();
        }

        // ★ タグの空白を正規化（全角/連続スペース → 半角1つ）
        $request->merge([
            'tags' => preg_replace('/\s+/u', ' ', trim((string) $request->input('tags'))),
        ]);

        $this->normalizePayload($request);

        [$rules, $messages, $attributes] = $this->rules(update: true);
        $rules['image'] = ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        $rules['remove_image'] = ['sometimes', 'boolean'];
        // ★ 自分が持つ会社のみ許可
        $rules['company_id'] = ['required', 'integer', Rule::in($allowedIds)];

        $data = $request->validate($rules, $messages, $attributes);

        $table = (new Job)->getTable();

        $full = [
            'title'           => $data['title'],
            'description'     => $data['body'],
            'company_id'      => (int) $data['company_id'],
            'company_name'    => $data['company_name']    ?? null,
            'location'        => $data['location']        ?? null,
            'employment_type' => $data['employment_type'] ?? null,
            'work_style'      => $data['work_style']      ?? null,
            'salary_from'     => $data['salary_from']     ?? null,
            'salary_to'       => $data['salary_to']       ?? null,
            'salary_unit'     => $data['salary_unit']     ?? null,
            'apply_url'       => $data['apply_url']       ?? null,
            'external_url'    => $data['external_url']    ?? null,
            'tags'            => $data['tags']            ?? null,
        ];

        if (Schema::hasColumn($table, 'excerpt') && isset($data['summary'])) {
            $full['excerpt'] = $data['summary'];
        }
        if (Schema::hasColumn($table, 'status') && isset($data['status'])) {
            $full['status'] = $data['status'];
        }
        if (Schema::hasColumn($table, 'is_published') && isset($data['status'])) {
            $full['is_published'] = $data['status'] === 'published' ? 1 : 0;
        }

        if ($request->boolean('remove_image') && $job->image_path) {
            Storage::disk('public')->delete($job->image_path);
            if (Schema::hasColumn($table, 'image_path')) {
                $full['image_path'] = null;
            }
        }
        if ($request->hasFile('image')) {
            if ($job->image_path) {
                Storage::disk('public')->delete($job->image_path);
            }
            $path = $request->file('image')->store('recruit_jobs', 'public');
            if (Schema::hasColumn($table, 'image_path')) {
                $full['image_path'] = $path;
            }
        }

        $cols    = Schema::getColumnListing($table);
        $payload = array_intersect_key($full, array_flip($cols));

        $job->fill($payload)->save();

        return redirect()->route('front.jobs.index')->with('success', '求人を更新しました。');
    }

    /** 削除（企業限定） */
    public function destroy(Job $job)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403);
        }

        if ($job->image_path) {
            Storage::disk('public')->delete($job->image_path);
        }
        $job->delete();

        return redirect()->route('front.jobs.index')->with('success', '求人を削除しました。');
    }

    /** body の正規化（description 等を吸収） */
    private function normalizePayload(Request $request): void
    {
        $body = $request->input('body');

        if ($body === null) {
            foreach (['description', 'content', 'text'] as $alt) {
                if ($request->filled($alt)) {
                    $body = $request->input($alt);
                    break;
                }
            }
        }

        if (is_string($body)) {
            $trimmed = preg_replace('/^[\s　]+|[\s　]+$/u', '', $body);
            $body = ($trimmed === '') ? '' : $body;
        }

        $request->merge(['body' => $body]);
    }

    /** 共通バリデーション */
    private function rules(bool $update = false): array
    {
        $rules = [
            'title'           => ['required', 'string', 'max:255'],
            'company_id'      => ['required', 'integer'], // 許可チェックは呼び出し側で Rule::in を付与
            'summary'         => ['required', 'string'],

            'employment_type' => ['required', 'string', Rule::in(['正社員','契約社員','アルバイト','業務委託','インターン'])],
            'salary_unit'     => ['required', 'string', Rule::in(['年収', '月収', '時給'])],

            'company_name'    => ['nullable', 'string', 'max:255'],

            // ★ 必須化した項目
            'location'        => ['required', 'string', 'max:255'],
            'work_style'      => ['required', 'string', Rule::in(['出社','フルリモート','ハイブリッド'])],

            'salary_from'     => ['nullable', 'integer', 'min:0'],
            'salary_to'       => ['nullable', 'integer', 'min:0', 'gte:salary_from'],
            'apply_url'       => ['nullable', 'url', 'max:512'],
            'external_url'    => ['nullable', 'url', 'max:512'],

            'body'            => ['required', 'string', 'min:30'],
            'status'          => [$update ? 'nullable' : 'required', Rule::in(['draft', 'published'])],

            // ★ 必須化
            'tags'            => ['required', 'string', 'max:255'],
        ];

        $messages = [
            'required' => ':attributeは必須です。',
            'integer'  => ':attributeは数値で指定してください。',
            'url'      => ':attributeの形式が正しくありません。',
            'gte'      => ':attributeは:other以上で指定してください。',
            'in'       => ':attributeの値が不正です。',
            'min'      => ':attributeは:min以上で指定してください。',
        ];

        $attributes = [
            'title'           => 'タイトル',
            'company_id'      => '企業',
            'company_name'    => '企業名',
            'summary'         => '概要',
            'location'        => '勤務地',
            'employment_type' => '雇用形態',
            'work_style'      => '働き方',
            'salary_from'     => '給与（下限）',
            'salary_to'       => '給与（上限）',
            'salary_unit'     => '単位',
            'apply_url'       => '応募ページURL',
            'external_url'    => '外部求人URL',
            'body'            => '本文',
            'status'          => '公開ステータス',
            'tags'            => 'タグ',
            'image'           => '画像',
        ];

        return [$rules, $messages, $attributes];
    }

    /**
     * ログインユーザーが操作可能な会社ID一覧と Company コレクションを返す
     * 優先順:
     *   A) ピボット company_user (company_profile_id) → company_profiles.company_name / name → companies.name 突合
     *   B) users.company_id
     *   C) companies.user_id / owner_user_id
     */
    private function companiesFor(int $userId): array
    {
        $companyTable = (new Company())->getTable();
        $ids = [];

        /* --- A) company_user( company_profile_id ) 経由 --- */
        if (Schema::hasTable('company_user') && Schema::hasColumn('company_user', 'company_profile_id')) {
            $cpTable = 'company_profiles';

            // company_profiles 側の「会社名カラム」を特定（company_name / name のどちらか）
            $cpNameCol = Schema::hasColumn($cpTable, 'company_name')
                ? 'company_name'
                : (Schema::hasColumn($cpTable, 'name') ? 'name' : null);

            $names = collect();

            if ($cpNameCol !== null) {
                // エイリアスを付けて pluck できるようにする
                $names = DB::table('company_user as cu')
                    ->join($cpTable.' as cp', 'cp.id', '=', 'cu.company_profile_id')
                    ->where('cu.user_id', $userId)
                    ->select('cp.'.$cpNameCol.' as cname')
                    ->pluck('cname')
                    ->filter(fn ($v) => is_string($v) && $v !== '')
                    ->map(fn ($v) => trim($v))
                    ->unique()
                    ->values();
            }

            if ($names->isNotEmpty()) {
                $idsFromNames = Company::whereIn('name', $names)
                    ->pluck('id')
                    ->map(fn ($v) => (int) $v)
                    ->all();
                $ids = array_merge($ids, $idsFromNames);
            }
        }

        /* --- B) users.company_id --- */
        if (Schema::hasColumn('users', 'company_id')) {
            $cid = DB::table('users')->where('id', $userId)->value('company_id');
            if ($cid) {
                $ids[] = (int) $cid;
            }
        }

        /* --- C) companies.user_id / owner_user_id --- */
        foreach (['user_id', 'owner_user_id'] as $ownerCol) {
            if (Schema::hasColumn($companyTable, $ownerCol)) {
                $own = DB::table($companyTable)
                    ->where($ownerCol, $userId)
                    ->pluck('id')
                    ->map(fn ($v) => (int) $v)
                    ->all();
                if (!empty($own)) {
                    $ids = array_merge($ids, $own);
                }
            }
        }

        // 重複排除
        $ids = array_values(array_unique($ids));

        // Company コレクション
        $companies = empty($ids)
            ? collect()
            : Company::whereIn('id', $ids)->get();

        return [$ids, $companies];
    }

    /* =======================================================
     * company_profiles の is_completed を満たす求人だけに絞るための結合
     * スキーマに応じて安全に JOIN する
     * ======================================================= */
    private function joinCompletedCompanyProfile($qb, string $jobTable)
    {
        if (! Schema::hasTable('company_profiles')) {
            return $qb->select("$jobTable.*");
        }

        $companyTable = (new Company)->getTable();

        // jobs/recruit_jobs に user_id がある場合
        if (Schema::hasColumn($jobTable, 'user_id')) {
            return $qb->join('company_profiles as cp', "cp.user_id", '=', "$jobTable.user_id")
                      ->where('cp.is_completed', true)
                      ->select("$jobTable.*");
        }

        // jobs/recruit_jobs に company_id がある場合は companies 経由でオーナー user_id を辿る
        if (Schema::hasColumn($jobTable, 'company_id') && Schema::hasTable($companyTable)) {
            $ownerCol = null;
            if (Schema::hasColumn($companyTable, 'user_id')) {
                $ownerCol = 'user_id';
            } elseif (Schema::hasColumn($companyTable, 'owner_user_id')) {
                $ownerCol = 'owner_user_id';
            }

            if ($ownerCol) {
                return $qb->join("$companyTable as c", "c.id", '=', "$jobTable.company_id")
                          ->join('company_profiles as cp', "cp.user_id", '=', "c.$ownerCol")
                          ->where('cp.is_completed', true)
                          ->select("$jobTable.*");
            }

            // 最終手段: company_profiles に company_id があればそれで突合
            if (Schema::hasColumn('company_profiles', 'company_id')) {
                return $qb->join('company_profiles as cp', 'cp.company_id', '=', "$jobTable.company_id")
                          ->where('cp.is_completed', true)
                          ->select("$jobTable.*");
            }
        }

        // どうしても突合できない場合はフィルタを諦めて落ちないようにする
        return $qb->select("$jobTable.*");
    }
}
