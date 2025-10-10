<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use App\Support\RoleResolver;
use App\Models\Job;

class FrontJobController extends Controller
{
    /** 求人一覧 */
    public function index(Request $request)
    {
        $q      = trim($request->input('q', ''));
        $status = $request->input('status', ''); // 'draft' | 'published' | ''

        $table = (new Job)->getTable();

        $jobs = Job::query()
            ->when($q !== '', function ($qb) use ($q, $table) {
                foreach (preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) as $kw) {
                    $qb->where(function ($qq) use ($kw, $table) {
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
            })
            ->when(in_array($status, ['draft','published'], true), function ($qb) use ($status, $table) {
                if (Schema::hasColumn($table, 'status')) {
                    $qb->where('status', $status);
                } elseif (Schema::hasColumn($table, 'is_published')) {
                    $qb->where('is_published', $status === 'published' ? 1 : 0);
                }
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('front.jobs.index', compact('jobs', 'q', 'status'));
    }

    /** 求人詳細（slug or id） */
    public function show(string $slugOrId)
    {
        $job = Job::query()
            ->when(is_numeric($slugOrId),
                fn ($q) => $q->where('id', $slugOrId),
                fn ($q) => $q->where('slug', $slugOrId)
            )->firstOrFail();

        return view()->exists('front.jobs.show')
            ? view('front.jobs.show', compact('job'))
            : view('front.jobs.index', ['jobs' => collect([$job]), 'q' => null, 'status' => null]);
    }

    /** 作成フォーム（企業限定） */
    public function create()
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403, '権限がありません。');
        }
        return view('front.jobs.create');
    }

    /** 登録（企業限定） */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') {
            abort(403, '権限がありません。');
        }

        // ★ normalize を追加
        $this->normalizePayload($request);

        [$rules, $messages, $attributes] = $this->rules();
        $data = $request->validate($rules, $messages, $attributes);

        // slug（重複ケア）
        $slugBase = Str::slug($data['title']) ?: Str::lower(Str::random(8));
        $slug = $slugBase; $i = 1;
        while (Job::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . (++$i);
        }

        $table = (new Job)->getTable();

        $full = [
            'title'           => $data['title'],
            'description'     => $data['body'], // ← name="body"
            'slug'            => $slug,
            'user_id'         => $user->id,
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

        if (Schema::hasColumn($table, 'status')) {
            $full['status'] = $data['status'];
        }
        if (Schema::hasColumn($table, 'is_published')) {
            $full['is_published'] = $data['status'] === 'published' ? 1 : 0;
        }

        $cols    = Schema::getColumnListing($table);
        $payload = array_intersect_key($full, array_flip($cols));

        Job::create($payload);

        return redirect()->route('front.jobs.index')->with('success', '求人を作成しました。');
    }

    /** 編集（企業限定） */
    public function edit(Job $job)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') abort(403);
        return view('front.jobs.edit', compact('job'));
    }

    /** 更新（企業限定） */
    public function update(Request $request, Job $job)
    {
        $user = Auth::user();
        if (!$user || RoleResolver::resolve($user) !== 'company') abort(403);

        // ★ normalize を追加
        $this->normalizePayload($request);

        [$rules, $messages, $attributes] = $this->rules(update: true);
        $data = $request->validate($rules, $messages, $attributes);

        $table = (new Job)->getTable();

        $full = [
            'title'           => $data['title'],
            'description'     => $data['body'],
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

        if (Schema::hasColumn($table, 'status') && isset($data['status'])) {
            $full['status'] = $data['status'];
        }
        if (Schema::hasColumn($table, 'is_published') && isset($data['status'])) {
            $full['is_published'] = $data['status'] === 'published' ? 1 : 0;
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
        if (!$user || RoleResolver::resolve($user) !== 'company') abort(403);
        $job->delete();
        return redirect()->route('front.jobs.index')->with('success', '求人を削除しました。');
    }

    /** ★ body の正規化（description などを吸収） */
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
            'title'           => ['required','string','max:255'],
            'company_name'    => ['nullable','string','max:255'],
            'location'        => ['nullable','string','max:255'],
            'employment_type' => ['nullable','string','max:50'],
            'work_style'      => ['nullable','string','max:50'],
            'salary_from'     => ['nullable','integer','min:0'],
            'salary_to'       => ['nullable','integer','min:0','gte:salary_from'],
            'salary_unit'     => ['nullable', Rule::in(['年収','月収','時給'])],
            'apply_url'       => ['nullable','url','max:512'],
            'external_url'    => ['nullable','url','max:512'],
            'body'            => ['required','string'],
            'status'          => [$update ? 'nullable' : 'required', Rule::in(['draft','published'])],
            'tags'            => ['nullable','string','max:255'],
        ];

        $messages = [
            'required' => ':attributeは必須です。',
            'url'      => ':attributeの形式が正しくありません。',
            'integer'  => ':attributeは数値で指定してください。',
            'gte'      => ':attributeは:other以上で指定してください。',
            'in'       => ':attributeの値が不正です。',
            'max'      => ':attributeは:max文字以内で指定してください。',
            'min'      => ':attributeは:min以上で指定してください。',
        ];

        $attributes = [
            'title'           => 'タイトル',
            'company_name'    => '企業名',
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
        ];

        return [$rules, $messages, $attributes];
    }
}
