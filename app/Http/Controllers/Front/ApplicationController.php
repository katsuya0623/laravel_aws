<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use App\Models\Application;
use App\Models\Job;

class ApplicationController extends Controller
{
    /** controller内だけで使う簡易キャッシュ */
    private array $columnsCache = [];

    /** 指定テーブルに列があるか（1リクエスト内キャッシュ） */
    private function hasCol(string $table, string $col): bool
    {
        if (!isset($this->columnsCache[$table])) {
            $this->columnsCache[$table] = collect(Schema::getColumnListing($table))->flip()->all();
        }
        return array_key_exists($col, $this->columnsCache[$table]);
    }

    /**
     * 応募履歴一覧（自分の分だけ）
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        $app   = new Application();
        $table = $app->getTable();

        $query = Application::query()->latest('id');

        // applications に user_id があれば user_id、無ければ email で本人絞り込み
        if ($this->hasCol($table, 'user_id')) {
            $query->where('user_id', $user->id);
        } elseif ($this->hasCol($table, 'email')) {
            $query->where('email', $user->email);
        }

        // 求人情報を一緒に取得（列がある環境のみ）
        if ($this->hasCol($table, 'job_id')) {
            $query->with(['job', 'job.company']);
        }

        $apps = $query->paginate(10);

        $statusLabels = [
            'applied'   => '応募済み',
            'reviewing' => '書類選考中',
            'interview' => '面接中',
            'offer'     => '内定',
            'rejected'  => 'お見送り',
            'pending'   => '保留',
        ];

        return view('mypage.applications.index', compact('apps', 'statusLabels'));
    }

    /**
     * 応募詳細（応募の状況を表示）
     */
    public function show(Request $request, Application $application)
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        $table = (new Application())->getTable();

        // 所有チェック：user_id が無い場合は email で本人確認
        $owns = $this->hasCol($table, 'user_id')
            ? ($application->user_id === $user->id)
            : ($this->hasCol($table, 'email') ? $application->email === $user->email : false);

        abort_unless($owns, 404);

        if ($this->hasCol($table, 'job_id')) {
            $application->load(['job', 'job.company']);
        }

        $statusLabels = [
            'applied'   => '応募済み',
            'reviewing' => '書類選考中',
            'interview' => '面接中',
            'offer'     => '内定',
            'rejected'  => 'お見送り',
            'pending'   => '保留',
        ];

        return view('mypage.applications.show', [
            'app'          => $application,
            'statusLabels' => $statusLabels,
        ]);
    }

    /**
     * 求人詳細からの応募（/recruit_jobs/{job}/apply）
     */
    public function store(Request $request, Job $job)
    {
        $appModel = new Application();
        $table    = $appModel->getTable();

        try {
            if (!$job || !$job->id) {
                abort(404, 'Job not found');
            }

            $user    = $request->user();
            $profile = $user?->profile;

            // バリデーション（email は未ログインなら必須）
            $rules = [
                'name'    => ['required', 'string', 'max:50'],
                'email'   => ['nullable', 'email', 'max:255'],
                'phone'   => ['nullable', 'string', 'max:50'],
                'message' => ['nullable', 'string', 'max:5000'],

                // 追加フィールド（applications に列があれば保存・無ければ捨てる）
                'gender'                  => ['nullable', 'in:male,female,other'],
                'birthday'                => ['nullable', 'date'],
                'address'                 => ['nullable', 'string', 'max:255'],
                'education'               => ['nullable', 'string', 'max:2000'],
                'current_status'          => ['nullable', 'string', 'max:100'],
                'desired_employment_type' => ['nullable', 'string', 'max:100'],
                'desired_type'            => ['nullable', 'string', 'max:100'], // Bladeのnameを吸収
                'motivation'              => ['nullable', 'string', 'max:3000'],
                'self_pr'                 => ['nullable', 'string', 'max:3000'],
            ];
            $data = $request->validate($rules);

            // alias: desired_type → desired_employment_type
            if (!empty($data['desired_type']) && empty($data['desired_employment_type'])) {
                $data['desired_employment_type'] = $data['desired_type'];
            }

            // 未ログインかつ email 未入力はエラー
            if (!$user && empty($data['email'])) {
                return back()
                    ->withInput()
                    ->withErrors(['email' => '未ログインでの応募はメールアドレスが必須です。']);
            }

            // email はフォーム優先、無ければログインユーザーのメール
            $email = $data['email'] ?? ($user->email ?? null);

            // プロフィールからの自動補完（フォーム未入力なら埋める）
            if ($profile) {
                // 揺れを吸収
                $profileBirthday = $profile->birthday ?? $profile->birthdate ?? null;
                $profileAddress  = $profile->address  ?? $profile->address_full ?? null;

                // ★ 学歴（JSON配列）からサマリを構築
                $educationSummary = $data['education'] ?? $this->buildEducationSummaryFromJson($profile);

                foreach ([
                    'gender'    => $profile->gender ?? null,
                    'birthday'  => $profileBirthday,
                    'address'   => $profileAddress,
                    'education' => $educationSummary,
                    'phone'     => $profile->phone ?? null,
                ] as $k => $v) {
                    if (empty($data[$k]) && !empty($v)) {
                        $data[$k] = $v;
                    }
                }
            }

            // ===== 重複応募チェック（job × (user_id or email)）=====
            $dup = Application::query()
                ->when($this->hasCol($table, 'job_id'), fn ($q) => $q->where('job_id', $job->id))
                ->when($this->hasCol($table, 'user_id') && $user, fn ($q) => $q->where('user_id', $user->id))
                ->when(
                    (!$this->hasCol($table, 'user_id') || !$user) && $this->hasCol($table, 'email') && $email,
                    fn ($q) => $q->where('email', $email)
                )
                ->exists();

            if ($dup) {
                return back()
                    ->withInput()
                    ->withErrors(['apply' => 'すでにこの求人へ応募済みです。マイページの応募履歴をご確認ください。']);
            }

            // ===== 保存（トランザクション）=====
            DB::beginTransaction();

            $app = new Application();

            // 必須系（存在する列だけ安全にセット）
            if ($this->hasCol($table, 'job_id'))           $app->job_id  = $job->id;
            if ($this->hasCol($table, 'user_id') && $user) $app->user_id = $user->id;
            if ($this->hasCol($table, 'email'))            $app->email   = $email;

            foreach (['name','phone','message'] as $k) {
                if ($this->hasCol($table, $k) && isset($data[$k])) {
                    $app->{$k} = $data[$k];
                }
            }

            // 追加フィールド（列がある時だけセット）
            foreach ([
                'gender','birthday','address','education',
                'current_status','desired_employment_type','motivation','self_pr',
            ] as $opt) {
                if ($this->hasCol($table, $opt) && array_key_exists($opt, $data)) {
                    $app->{$opt} = $data[$opt];
                }
            }

            // ステータス初期値
            if ($this->hasCol($table, 'status') && empty($app->status)) {
                $app->status = 'applied';
            }

            $app->save();

            // プロフィールの空欄を自動補完（上書きしない）
            if ($user && method_exists($user, 'profile')) {
                $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

                $fillable = Arr::only($data, [
                    'gender','birthday','address','education','phone',
                ]);

                $patch = [];
                foreach ($fillable as $k => $v) {
                    if ($v !== null && ($profile->{$k} ?? null) === null) {
                        $patch[$k] = $v;
                    }
                }
                if ($patch) {
                    $profile->fill($patch)->save();
                }
            }

            DB::commit();

            return redirect()
                ->route('mypage.applications.index')
                ->with('status', '応募を受け付けました。');

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('[Application apply failed]', [
                'error'        => $e->getMessage(),
                'job_id'       => $job->id ?? null,
                'user_id'      => $request->user()->id ?? null,
                'payload_keys' => array_keys($request->except(['_token'])),
            ]);

            return back()
                ->withInput()
                ->withErrors(['apply' => '応募処理でエラーが発生しました。しばらくしてから再度お試しください。']);
        }
    }

    /**
     * 応募フォーム表示（GET） /recruit_jobs/{job}/apply
     * - 学歴サマリを JSON（profiles.educations）から作成して一時属性へ
     * - birthdate/birthday、address/address_full も補完
     */
    public function create(Request $request, Job $job)
    {
        $user    = $request->user();
        $profile = $user?->profile;

        $eduSummary = null;

        if ($profile) {
            // ① 既に単一カラムがあればそれを優先
            $eduSummary = $profile->education_summary
                ?? $profile->education
                ?? null;

            // ② 無ければ JSON（educations）から作る
            if (!$eduSummary) {
                $eduSummary = $this->buildEducationSummaryFromJson($profile);
            }

            // ③ 見つかったら一時属性に入れて Blade で使えるように
            if ($eduSummary) {
                $profile->setAttribute('education_summary', $eduSummary);
            }

            // ④ birthdate / address_full の揺れ補完
            if (($profile->birthdate ?? null) && !isset($profile->birthday)) {
                $profile->setAttribute('birthday', $profile->birthdate);
            }
            if (($profile->address_full ?? null) && !isset($profile->address)) {
                $profile->setAttribute('address', $profile->address_full);
            }
        }

        Log::debug('[apply.create] education summary source', [
            'user_id' => $user?->id,
            'summary' => $eduSummary,
            'source'  => $eduSummary ? 'profiles.educations(json or single field)' : null,
        ]);

        return view('front.jobs.apply', [
            'job'        => $job,
            'user'       => $user,
            'profile'    => $profile,
            'eduSummary' => $eduSummary,
        ]);
    }

    /**
     * profiles.educations(JSON) から学歴サマリ文字列を作る
     * 期待スキーマ: [{school, faculty, department, period_from, period_to, status}, ...]
     */
    private function buildEducationSummaryFromJson($profile): ?string
    {
        $rows = $profile?->educations; // casts: array
        if (!is_array($rows) || empty($rows)) {
            return null;
        }

        // 最新を1件選ぶ（period_to > period_from）
        $latest = collect($rows)->sortByDesc(function ($e) {
            $end   = Arr::get($e, 'period_to');
            $start = Arr::get($e, 'period_from');
            return $end ?: $start ?: now()->toDateString();
        })->first();

        if (!$latest) return null;

        $school  = trim((string) Arr::get($latest, 'school', ''));
        $faculty = trim((string) Arr::get($latest, 'faculty', ''));
        $dept    = trim((string) Arr::get($latest, 'department', ''));
        $status  = trim((string) Arr::get($latest, 'status', '')); // 卒業/在学/中退など
        $dateRaw = Arr::get($latest, 'period_to') ?: Arr::get($latest, 'period_from');
        $when    = $dateRaw ? Carbon::parse($dateRaw)->isoFormat('YYYY/MM') : null;

        $baseParts = array_filter([$school, $faculty, $dept], fn ($v) => $v !== '');
        $base      = implode(' ', $baseParts);

        if ($base === '') return null;

        $tail = $status && $when ? "（{$status} {$when}）"
             : ($status ? "（{$status}）"
             : ($when ? "（{$when}）" : null));

        return trim($base . ($tail ? " {$tail}" : ''));
    }
}
