<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Support\RoleResolver;
use App\Services\CompanyAutoLinker;

class CompanyProfileController extends Controller
{
    /** 編集画面 */
    public function edit()
    {
        // 会社が無ければ自動作成（slug も自動採番）
        $company = $this->resolveCompanyForUser(true);
        if (!$company) {
            abort(403, '会社が見つかりません。管理者にお問い合わせください。');
        }

        // slug 列が存在していて空の旧データを救済
        if (Schema::hasColumn('companies', 'slug') && empty($company->slug)) {
            $company->slug = $this->generateUniqueCompanySlug($company->name ?: 'company');
            $company->save();
        }

        // company_id ベース（user_id は参照しない）
        $profile = CompanyProfile::where('company_id', $company->id)->first();

        if (!$profile) {
            // 初期プロファイル（まだ DB に無い）
            $profile = new CompanyProfile([
                'company_id'   => $company->id,
                // 互換: company_name / name 列があれば初期値を入れる
                'company_name' => Schema::hasColumn('company_profiles', 'company_name') ? ($company->name ?? null) : null,
                'name'         => Schema::hasColumn('company_profiles', 'name')         ? ($company->name ?? null) : null,
            ]);
        }

        $merged = $this->normalizeProfileForView($profile, $company);
        $role   = RoleResolver::resolve(Auth::user());

        return view('company.edit', [
            'company' => $merged,
            'role'    => $role,
        ]);
    }

    /** 新規登録 or 更新（POST用） */
    public function store(Request $request)
    {
        return $this->saveProfile($request);
    }

    /** 更新（POST/PUT/PATCH用） */
    public function update(Request $request)
    {
        return $this->saveProfile($request);
    }

    /** 共通の保存処理（company_id に一本化） */
    private function saveProfile(Request $request)
    {
        // 会社が無ければ自動作成（slug も自動採番）
        $company = $this->resolveCompanyForUser(true);
        if (!$company) {
            abort(403, '会社が見つかりません。管理者にお問い合わせください。');
        }

        // ▼ アップロード基本チェック
        if (isset($_FILES['logo']) && is_array($_FILES['logo'])) {
            $err = $_FILES['logo']['error'] ?? 0;
            if ($err === UPLOAD_ERR_INI_SIZE)  return back()->withErrors(['logo' => 'サーバ設定の「upload_max_filesize」を超えています。'])->withInput();
            if ($err === UPLOAD_ERR_FORM_SIZE) return back()->withErrors(['logo' => 'フォーム側のファイルサイズ制限（10MB）を超えています。'])->withInput();
            if ($err !== UPLOAD_ERR_OK && $err !== UPLOAD_ERR_NO_FILE) {
                return back()->withErrors(['logo' => "ファイルアップロード中にエラーが発生しました（コード: {$err}）。"])->withInput();
            }
        }

        // ▼ バリデーション
        $data = $request->validate([
            'company_name'       => ['nullable', 'string', 'max:30'],
            'company_name_kana'  => ['required', 'string', 'max:255', 'regex:/^[ァ-ヶー－\s　]+$/u'],
            'description'        => ['required', 'string', 'max:2000'],
            'website_url'        => ['nullable', 'url', 'max:255'],
            'email'              => ['nullable', 'email', 'max:255'],
            'tel'                => ['nullable', 'string', 'max:20', 'regex:/^\+?\d[\d\-\(\)\s]{6,}$/'],
            'postal_code'        => ['required', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture'         => ['required', 'string', 'max:255'],
            'city'               => ['required', 'string', 'max:255'],
            'address1'           => ['required', 'string', 'max:255'],
            'address2'           => ['nullable', 'string', 'max:255'],
            'industry'           => ['required', 'string', 'max:255'],
            'employees'          => ['required', 'integer', 'min:1', 'max:1000000'],
            'founded_on'         => ['nullable', 'date', 'before_or_equal:today'],

            'logo' => [
                'nullable',
                'file',
                'max:10240',
                function ($attr, $file, $fail) {
                    if (!($file instanceof \Illuminate\Http\UploadedFile)) return;

                    $name = $file->getClientOriginalName() ?? '';
                    $extFromName = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                    // MIME が null でも落ちないように
                    $mime = strtolower((string) ($file->getMimeType() ?? ''));
                    $map  = [
                        'image/jpeg' => 'jpg',
                        'image/jpg'  => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                        'image/svg+xml' => 'svg',
                    ];
                    $extFromMime = $map[$mime] ?? null;

                    $ext = $extFromName ?: ($extFromMime ?? '');
                    if ($ext === 'jpeg') $ext = 'jpg';

                    $allowed = ['jpg','png','webp','svg','svgz'];
                    if (!in_array($ext, $allowed, true)) {
                        \Log::info('logo upload reject', [
                            'client_name'=>$name,
                            'mime'=>$mime,
                            'ext_from_name'=>$extFromName,
                            'ext_from_mime'=>$extFromMime
                        ]);
                        return $fail('対応していないファイル形式です（jpg / png / webp / svg）。');
                    }
                },
            ],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        // 1社1プロフィール（company_id 固定）
        $profile = CompanyProfile::firstOrNew(['company_id' => $company->id]);

        // ===== 入力 → DB 列マッピング =====
        // 会社名（互換列が存在すれば反映）
        if (!empty($data['company_name'])) {
            if (Schema::hasColumn('company_profiles', 'company_name')) $profile->company_name = $data['company_name'];
            if (Schema::hasColumn('company_profiles', 'name'))         $profile->name         = $data['company_name'];
        }

        // カナ
        if (Schema::hasColumn('company_profiles', 'kana')) {
            $profile->kana = $data['company_name_kana'] ?? $profile->kana;
        } elseif (Schema::hasColumn('company_profiles', 'company_name_kana')) {
            $profile->company_name_kana = $data['company_name_kana'] ?? $profile->company_name_kana;
        }

        // 基本情報
        foreach ([
            'description' => 'description',
            'website_url' => 'website_url',
            'email'       => 'email',
            'tel'         => 'phone',        // tel -> phone
            'postal_code' => 'postal_code',
            'prefecture'  => 'prefecture',
            'city'        => 'city',
            'address1'    => 'address1',
            'address2'    => 'address2',
            'industry'    => 'industry',
        ] as $input => $column) {
            if (Schema::hasColumn('company_profiles', $column)) {
                $profile->{$column} = $data[$input] ?? $profile->{$column};
            }
        }

        // 人数
        if (Schema::hasColumn('company_profiles', 'employees_count')) {
            $profile->employees_count = $data['employees'] ?? $profile->employees_count;
        } elseif (Schema::hasColumn('company_profiles', 'employees')) {
            $profile->employees = $data['employees'] ?? $profile->employees;
        }

        // 設立日
        if (Schema::hasColumn('company_profiles', 'founded_at')) {
            $profile->founded_at = $data['founded_on'] ?? $profile->founded_at;
        } elseif (Schema::hasColumn('company_profiles', 'founded_on')) {
            $profile->founded_on = $data['founded_on'] ?? $profile->founded_on;
        }

        // ロゴ削除
        if ($request->boolean('remove_logo') && Schema::hasColumn('company_profiles', 'logo_path')) {
            if ($profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $profile->logo_path = null;
        }

        // ロゴ保存
        if ($request->hasFile('logo') && Schema::hasColumn('company_profiles', 'logo_path')) {
            $newPath = $this->storeLogo($request->file('logo'));
            if ($profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $profile->logo_path = $newPath;
        }

        // 完了判定
        if (Schema::hasColumn('company_profiles', 'is_completed')) {
            $profile->is_completed = $this->judgeCompleted($profile);
        }

        $profile->company_id = $company->id; // 念のため
        $profile->save();

        // 会社名は Company 側にも同期（存在する列のみ）
        if (!empty($data['company_name']) && Schema::hasColumn('companies', 'name')) {
            $company->name = $data['company_name'];
            // slug は既存リンク保護のため維持。空のときだけ補完。
            if (Schema::hasColumn('companies', 'slug') && empty($company->slug)) {
                $company->slug = $this->generateUniqueCompanySlug($company->name);
            }
            $company->save();
        }

        // 企業名から Company 自動連携
        // ★ 分身防止：create_if_missing=false ＆ IDで明示リンク
        try {
            CompanyAutoLinker::link(Auth::user(), [
                'company_id'        => $company->id,
                'create_if_missing' => false,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('auto-link failed', ['error' => $e->getMessage()]);
        }

        return redirect()->route('user.company.edit')->with('status', '企業情報を保存しました。');
    }

    /** ロゴ保存 */
    private function storeLogo(UploadedFile $file): string
    {
        $dir = 'company_logos';
        if (!Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }

        $nameOrig = $file->getClientOriginalName() ?? '';
        $ext = strtolower(pathinfo($nameOrig, PATHINFO_EXTENSION));
        $mime = strtolower((string) ($file->getMimeType() ?? ''));

        // フォールバック（拡張子無しアップロード救済）
        if ($ext === '') {
            $map = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/svg+xml'=>'svg'];
            $ext = $map[$mime] ?? 'png';
        }
        if ($ext === 'jpeg') $ext = 'jpg';

        if (in_array($ext, ['svg','svgz'], true)) {
            $svg = @file_get_contents($file->getRealPath());
            if ($svg !== false && preg_match('/<(script|iframe|object|embed)|on\w+=/i', $svg)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'logo' => 'SVG内にスクリプト等は含められません。',
                ]);
            }
        }

        $name = now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $file->storeAs($dir, $name, 'public');

        \Log::info('logo saved', [
            'saved_path'  => $dir.'/'.$name,
            'client_name' => $nameOrig,
            'mime'        => $mime,
            'ext'         => $ext,
        ]);

        return $dir . '/' . $name;
    }

    /** 表示用正規化（古い列名→現行フォーム項目に寄せる） */
    private function normalizeProfileForView(CompanyProfile $profile, ?Company $company): CompanyProfile
    {
        $p = clone $profile;

        $legacyToCurrent = [
            'company_name_kana' => ['company_kana', 'kana'],
            'description'       => ['intro', 'description'],
            'tel'               => ['phone'],
            'founded_on'        => ['founded_at'],
        ];

        foreach ($legacyToCurrent as $cur => $cands) {
            if (filled($p->{$cur} ?? null)) continue;
            foreach ($cands as $cand) {
                $val = $p->getAttribute($cand);
                if ($val !== null && $val !== '') {
                    if ($cur === 'founded_on' && $cand === 'founded_at') {
                        $val = Carbon::parse($val)->toDateString();
                    }
                    $p->{$cur} = $val;
                    break;
                }
            }
        }

        if ($company) {
            $map = [
                'description'       => ['description'],
                'website_url'       => ['website_url','site_url','url'],
                'email'             => ['email'],
                'tel'               => ['tel','phone','phone_number'],
                'postal_code'       => ['postal_code','zip'],
                'prefecture'        => ['prefecture','state'],
                'city'              => ['city'],
                'address1'          => ['address1','street'],
                'address2'          => ['address2'],
                'industry'          => ['industry'],
                'employees'         => ['employees','employees_count'],
                'founded_on'        => ['founded_on','founded_at'],
                'logo_path'         => ['logo_path','logo'],
                'company_name'      => ['name'],
                'company_name_kana' => ['name_kana','company_kana','kana'],
            ];

            $schema = Schema::connection($company->getConnectionName() ?: config('database.default'));

            foreach ($map as $key => $cands) {
                if (filled($p->{$key} ?? null)) continue;
                foreach ($cands as $ck) {
                    if ($schema->hasColumn($company->getTable(), $ck)) {
                        $val = $company->{$ck};
                        if ($val !== null && $val !== '') {
                            if ($key === 'founded_on' && $ck === 'founded_at') {
                                $val = Carbon::parse($val)->toDateString();
                            }
                            $p->{$key} = $val;
                            break;
                        }
                    }
                }
            }
        }

        return $p;
    }

    // ===== ここから会社解決＆slug生成ユーティリティ =====

    /** ユニークな slug を作る */
    private function generateUniqueCompanySlug(string $name): string
    {
        // 日本語名も含めて slug 化（intl 拡張があればローマ字化）
        $base = Str::slug($name, '-');
        if ($base === '') {
            // どうしても slug 化できない場合のフォールバック
            $base = 'company-' . Str::lower(Str::random(6));
        }

        $slug = $base;
        $i = 2;

        while (
            Schema::hasTable('companies') &&
            \DB::table('companies')->where('slug', $slug)->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    /**
     * ログインユーザーに紐づく Company を解決
     * - company_user ピボットがあれば最優先
     * - それが無ければ companies.user_id をフォールバック
     * - $autoCreate=true のときは無ければ自動作成（slug 必須）
     */
    private function resolveCompanyForUser(bool $autoCreate = false): ?Company
    {
        $user = Auth::user();
        $userId = $user?->id;
        if (!$userId) return null;

        // 1) ピボット最優先
        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'user_id')
            && Schema::hasColumn('company_user', 'company_id')) {
            $companyId = \DB::table('company_user')->where('user_id', $userId)->value('company_id');
            if ($companyId && ($c = Company::find($companyId))) {
                return $c;
            }
        }

        // 2) companies.user_id
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
            if ($c = Company::where('user_id', $userId)->first()) {
                // ピボットがあれば張っておく
                if (Schema::hasTable('company_user')
                    && Schema::hasColumn('company_user', 'user_id')
                    && Schema::hasColumn('company_user', 'company_id')) {
                    $exists = \DB::table('company_user')
                        ->where('user_id', $userId)
                        ->where('company_id', $c->id)
                        ->exists();
                    if (!$exists) {
                        \DB::table('company_user')->insert([
                            'user_id' => $userId,
                            'company_id' => $c->id,
                        ]);
                    }
                }
                return $c;
            }
        }

        // 3) belongsToMany フォールバック
        try {
            if (method_exists(\App\Models\User::class, 'companies') && $user) {
                if ($c = $user->companies()->first()) return $c;
            }
        } catch (\Throwable $e) {}

        // 4) 自動作成
        if (!$autoCreate || !Schema::hasTable('companies')) {
            return null;
        }

        $nameCandidate = (string) ($user->company_name ?? $user->name ?? '未設定の会社');
        $name = Str::limit(trim($nameCandidate) !== '' ? $nameCandidate : '未設定の会社', 30, '');

        $company = new Company();

        // 必須列を存在チェックしながら埋める
        if (Schema::hasColumn('companies', 'name'))    $company->name = $name;
        if (Schema::hasColumn('companies', 'slug'))    $company->slug = $this->generateUniqueCompanySlug($name);
        if (Schema::hasColumn('companies', 'user_id')) $company->user_id = $userId;

        // フラグ列があれば初期化
        if (Schema::hasColumn('companies', 'status') && empty($company->status)) $company->status = 'draft';
        if (Schema::hasColumn('companies', 'is_public'))    $company->is_public = 0;
        if (Schema::hasColumn('companies', 'is_published')) $company->is_published = 0;
        if (Schema::hasColumn('companies', 'published'))    $company->published = 0;

        $company->save();

        // ピボットがあれば紐付け
        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'user_id')
            && Schema::hasColumn('company_user', 'company_id')) {
            \DB::table('company_user')->updateOrInsert(
                ['user_id' => $userId],
                ['company_id' => $company->id]
            );
        }

        return $company;
    }

    /** 完了条件の簡易判定 */
    private function judgeCompleted(CompanyProfile $p): bool
    {
        $need = 0; $have = 0;

        foreach (['description','website_url','email','phone','postal_code','prefecture','city','address1','industry'] as $col) {
            if (Schema::hasColumn('company_profiles', $col)) {
                $need++;
                if (!empty($p->{$col})) $have++;
            }
        }
        if (Schema::hasColumn('company_profiles','logo_path')) {
            $need++;
            if (!empty($p->logo_path)) $have++;
        }

        return $need > 0 ? ($have / $need) >= 0.6 : false;
    }
}
