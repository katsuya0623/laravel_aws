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
use App\Services\LogoStorage;
use App\Support\RoleResolver;
use App\Services\CompanyAutoLinker;

class CompanyProfileController extends Controller
{
    /** 編集画面 */
    public function edit()
    {
        $company = $this->resolveCompanyForUser();

        if ($company) {
            $profile = CompanyProfile::where('company_id', $company->id)->orderByDesc('id')->first();
            if (!$profile) {
                $profile = new CompanyProfile([
                    'company_id'   => $company->id,
                    'user_id'      => $company->user_id ?? Auth::id(),
                    'company_name' => $company->name,
                ]);
            }
        } else {
            $profile = CompanyProfile::firstOrNew(['user_id' => Auth::id()]);
        }

        $merged = $this->normalizeProfileForView($profile, $company);
        $role = RoleResolver::resolve(Auth::user());

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

    /** 共通の保存処理 */
    private function saveProfile(Request $request)
    {
        // ▼ ファイルアップロードエラーの早期検出
        if (isset($_FILES['logo']) && is_array($_FILES['logo'])) {
            $err = $_FILES['logo']['error'] ?? 0;

            if ($err === UPLOAD_ERR_INI_SIZE) {
                return back()->withErrors(['logo' => 'サーバ設定の「upload_max_filesize」を超えています。'])->withInput();
            }
            if ($err === UPLOAD_ERR_FORM_SIZE) {
                return back()->withErrors(['logo' => 'フォーム側のファイルサイズ制限（10MB）を超えています。'])->withInput();
            }
            if ($err !== UPLOAD_ERR_OK && $err !== UPLOAD_ERR_NO_FILE) {
                return back()->withErrors(['logo' => 'ファイルアップロード中にエラーが発生しました（コード: ' . $err . '）。'])->withInput();
            }
        }

        // ▼ 通常のバリデーション（拡張子ベース）
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

                    // 拡張子判定（fileinfoが無くても動くように修正）
                    $extFromName = strtolower(pathinfo($file->getClientOriginalName() ?? '', PATHINFO_EXTENSION));
                    $mime = strtolower((string) $file->getMimeType());
                    $map = [
                        'image/jpeg' => 'jpg',
                        'image/jpg'  => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                        'image/svg+xml' => 'svg',
                    ];
                    $extFromMime = $map[$mime] ?? null;
                    $ext = $extFromName ?: $extFromMime ?: '';
                    if ($ext === 'jpeg') $ext = 'jpg';

                    $allowed = ['jpg','png','webp','svg','svgz'];
                    if (!in_array($ext, $allowed, true)) {
                        \Log::info('logo upload reject', [
                            'client_name' => $file->getClientOriginalName(),
                            'mime' => $mime,
                            'ext_from_name' => $extFromName,
                            'ext_from_mime' => $extFromMime,
                        ]);
                        return $fail('対応していないファイル形式です（jpg / png / webp / svg）。');
                    }
                },
            ],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $linkedCompany = $this->resolveCompanyForUser();

        if ($linkedCompany) {
            $profile = CompanyProfile::where('company_id', $linkedCompany->id)->orderByDesc('id')->first();
            if (!$profile) {
                $profile = new CompanyProfile([
                    'company_id'   => $linkedCompany->id,
                    'company_name' => $linkedCompany->name,
                ]);
            }
        } else {
            $profile = CompanyProfile::firstOrNew(['user_id' => Auth::id()]);
        }

        unset($data['company_name']);
        $profile->fill($data);
        $profile->user_id = Auth::id();

        if ($linkedCompany) {
            $profile->company_id = $linkedCompany->id;
            if (empty($profile->company_name)) {
                $profile->company_name = $linkedCompany->name;
            }
        }

        // ロゴ削除
        if ($request->boolean('remove_logo')) {
            if ($profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $profile->logo_path = null;
        }

        // ロゴ保存
        if ($request->hasFile('logo')) {
            $newPath = $this->storeLogo($request->file('logo'));
            if ($profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $profile->logo_path = $newPath;
        }

        $profile->save();

        if (method_exists($profile, 'syncCompletionFlags')) {
            $profile->syncCompletionFlags();
        }

        try {
            CompanyAutoLinker::link(Auth::user(), [
                'company_name'      => $profile->company_name ?? null,
                'create_if_missing' => true,
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

        $ext = strtolower(pathinfo($file->getClientOriginalName() ?? '', PATHINFO_EXTENSION));
        $mime = strtolower((string) $file->getMimeType());
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];
        $ext = $ext ?: ($map[$mime] ?? 'svg');
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
            'saved_path' => $dir.'/'.$name,
            'client_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'ext'  => $ext,
        ]);

        return $dir . '/' . $name;
    }

    /** 表示用正規化 */
    private function normalizeProfileForView(CompanyProfile $profile, ?Company $company): CompanyProfile
    {
        $p = clone $profile;

        $legacyToCurrent = [
            'company_name_kana' => ['company_kana'],
            'description'       => ['intro'],
            'tel'               => ['phone'],
            'founded_on'        => ['founded_at'],
        ];

        foreach ($legacyToCurrent as $cur => $cands) {
            if (filled($p->{$cur})) continue;
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
                'description' => ['description'],
                'website_url' => ['website_url', 'site_url', 'url'],
                'email' => ['email'],
                'tel' => ['tel', 'phone', 'phone_number'],
                'postal_code' => ['postal_code', 'zip'],
                'prefecture' => ['prefecture', 'state'],
                'city' => ['city'],
                'address1' => ['address1', 'street'],
                'address2' => ['address2'],
                'industry' => ['industry'],
                'employees' => ['employees'],
                'founded_on' => ['founded_on', 'founded_at'],
                'logo_path' => ['logo_path', 'logo'],
                'company_name' => ['name'],
                'company_name_kana' => ['name_kana', 'company_kana'],
            ];

            $conn = $company->getConnectionName() ?: config('database.default');
            $schema = Schema::connection($conn);

            foreach ($map as $key => $cands) {
                if (filled($p->{$key})) continue;
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

    /** ログインユーザーに紐づく Company を推定 */
    private function resolveCompanyForUser(): ?Company
    {
        $userId = Auth::id();

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
            if ($c = Company::where('user_id', $userId)->first()) return $c;
        }
        if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles', 'company_id')) {
            $cid = CompanyProfile::where('user_id', $userId)->value('company_id');
            if ($cid && ($c = Company::find($cid))) return $c;
        }
        if (
            Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'user_id')
            && Schema::hasColumn('company_user', 'company_id')
        ) {
            $cid = \DB::table('company_user')->where('user_id', $userId)->value('company_id');
            if ($cid && ($c = Company::find($cid))) return $c;
        }
        return null;
    }
}
