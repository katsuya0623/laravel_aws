<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;        // ★ 追加
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            $profile = CompanyProfile::firstOrCreate(
                ['company_id' => $company->id],
                [
                    'user_id'           => $company->user_id ?? Auth::id(),
                    'company_name'      => $company->name,
                    'company_name_kana' => null,
                ]
            );
        } else {
            $profile = CompanyProfile::firstOrNew(['user_id' => Auth::id()]);
        }

        $role = RoleResolver::resolve(Auth::user());

        return view('company.edit', [
            'company' => $profile, // Blade 側は $company 変数を使っているため名称そのまま
            'role'    => $role,
        ]);
    }

    public function store(Request $request) { return $this->saveProfile($request); }
    public function update(Request $request) { return $this->saveProfile($request); }

    /** 共通の保存処理（DB 直 upsert 版） */
    private function saveProfile(Request $request)
    {
        // 会社名はフォームに来ても後で必ず無視（企業側は変更不可）
        $data = $request->validate(
            [
                'company_name'       => ['nullable','string','max:30'],
                'company_name_kana'  => ['required','string','max:255','regex:/^[ァ-ヶー－\s　]+$/u'],
                'description'        => ['required','string','max:2000'],
                'website_url'        => ['nullable','url','max:255'],
                'email'              => ['nullable','email','max:255'],
                'tel'                => ['nullable','string','max:20','regex:/^\+?\d[\d\-\(\)\s]{6,}$/'],
                'postal_code'        => ['required','regex:/^\d{3}-?\d{4}$/'],
                'prefecture'         => ['required','string','max:255'],
                'city'               => ['required','string','max:255'],
                'address1'           => ['required','string','max:255'],
                'address2'           => ['nullable','string','max:255'],
                'industry'           => ['required','string','max:255'],
                'employees'          => ['required','integer','min:1','max:1000000'],
                'founded_on'         => ['nullable','date','before_or_equal:today'],
                'logo'               => ['nullable','file','max:10240','mimes:jpg,jpeg,png,webp,svg,svgz','mimetypes:image/jpeg,image/png,image/webp,image/svg+xml,application/xml,text/xml'],
                'remove_logo'        => ['sometimes','boolean'],
            ],
            [
                'company_name.max'             => '会社名は30文字以内で入力してください。',
                'company_name_kana.required'   => '会社名（カナ）は必須です。',
                'company_name_kana.regex'      => '会社名（カナ）は全角カタカナで入力してください。',
                'description.required'         => '事業内容 / 紹介は必須です。',
                'description.max'              => '事業内容 / 紹介は2000文字以内で入力してください。',
                'website_url.url'              => 'Webサイトの形式が正しくありません。',
                'email.email'                  => '代表メールの形式が正しくありません。',
                'tel.regex'                    => '電話番号の形式が正しくありません。',
                'postal_code.required'         => '郵便番号は必須です。',
                'postal_code.regex'            => '郵便番号は 123-4567 の形式で入力してください（ハイフン無しも可）。',
                'prefecture.required'          => '都道府県は必須です。',
                'city.required'                => '市区町村は必須です。',
                'address1.required'            => '番地・建物は必須です。',
                'industry.required'            => '業種は必須です。',
                'employees.required'           => '従業員数は必須です。',
                'employees.integer'            => '従業員数は整数で入力してください。',
                'employees.min'                => '従業員数は1以上で入力してください。',
                'employees.max'                => '従業員数が大きすぎます。',
                'founded_on.before_or_equal'   => '設立日は本日以前の日付を指定してください。',
            ]
        );

        // 会社特定
        $linkedCompany = $this->resolveCompanyForUser();

        // 会社名は企業側で変更不可：常に無視（既存値保持）
        unset($data['company_name']);

        // まず既存レコードを company_id 優先 / なければ user_id で取得
        $where = [];
        if ($linkedCompany && Schema::hasColumn('company_profiles', 'company_id')) {
            $where['company_id'] = $linkedCompany->id;
        } else {
            $where['user_id'] = Auth::id();
        }

        // 既存行
        $existing = DB::table('company_profiles')->where($where)->first();

        // ロゴ削除 / 差し替えに備えて、現在の logo_path を把握
        $currentLogo = $existing->logo_path ?? null;

        // ロゴ削除
        if ($request->boolean('remove_logo')) {
            if ($currentLogo && Storage::disk('public')->exists($currentLogo)) {
                Storage::disk('public')->delete($currentLogo);
            }
            $currentLogo = null;
        }

        // ロゴアップロード差し替え
        if ($request->hasFile('logo')) {
            $newPath = $this->storeLogo($request->file('logo'));
            if ($currentLogo && Storage::disk('public')->exists($currentLogo)) {
                Storage::disk('public')->delete($currentLogo);
            }
            $currentLogo = $newPath;
        }

        // upsert 用のペイロードを構築（存在するカラムだけ入れる）
        $columns = [
            'company_name_kana','description','website_url','email','tel',
            'postal_code','prefecture','city','address1','address2',
            'industry','employees','founded_on','logo_path',
        ];
        $payload = [];
        foreach ($columns as $col) {
            if ($col === 'logo_path') {
                $payload['logo_path'] = $currentLogo;
                continue;
            }
            if (Schema::hasColumn('company_profiles', $col)) {
                $payload[$col] = $data[$col] ?? null;
            }
        }

        // 主キー系を明示
        $payload['user_id'] = Auth::id();
        if ($linkedCompany && Schema::hasColumn('company_profiles', 'company_id')) {
            $payload['company_id'] = $linkedCompany->id;
        }
        if (Schema::hasColumn('company_profiles', 'company_name') && $linkedCompany) {
            // 初期化（空なら companies.name を持っておく）
            $payload['company_name'] = $existing->company_name ?? $linkedCompany->name;
        }

        // タイムスタンプ
        $now = now();
        if ($existing) {
            if (Schema::hasColumn('company_profiles', 'updated_at')) {
                $payload['updated_at'] = $now;
            }
            DB::table('company_profiles')->where('id', $existing->id)->update($payload);
        } else {
            if (Schema::hasColumn('company_profiles', 'created_at')) {
                $payload['created_at'] = $now;
            }
            if (Schema::hasColumn('company_profiles', 'updated_at')) {
                $payload['updated_at'] = $now;
            }
            DB::table('company_profiles')->updateOrInsert($where, $payload);
        }

        // 完了フラグ同期（存在すれば）
        if (class_exists(CompanyProfile::class)) {
            $profile = CompanyProfile::where($where)->first();
            if ($profile && method_exists($profile, 'syncCompletionFlags')) {
                $profile->syncCompletionFlags();
            }
        }

        // オートリンク（必要に応じて）
        try {
            CompanyAutoLinker::link(
                Auth::user(),
                [
                    'company_name'      => $linkedCompany->name ?? ($existing->company_name ?? null),
                    'create_if_missing' => true,
                ]
            );
        } catch (\Throwable $e) {
            \Log::warning('auto-link failed after company profile save', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);
        }

        return redirect()->route('user.company.edit')->with('status', '企業情報を保存しました。');
    }

    /** ロゴ保存（サービス優先／フォールバックあり） */
    private function storeLogo(UploadedFile $file): string
    {
        if (class_exists(LogoStorage::class)) {
            try {
                $stored = app(LogoStorage::class)->store($file);
                if (is_string($stored) && $stored !== '') {
                    return ltrim($stored, '/');
                }
            } catch (\Throwable $e) { /* fallback */ }
        }

        $dir  = 'company_logos';
        $ext  = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'svg');
        $name = now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        return $file->storeAs($dir, $name, 'public');
    }

    /** ログインユーザーに紐づく Company を推定 */
    private function resolveCompanyForUser(): ?Company
    {
        $userId = Auth::id();

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
            if ($c = Company::where('user_id', $userId)->first()) {
                return $c;
            }
        }

        if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles', 'company_id')) {
            $companyId = CompanyProfile::where('user_id', $userId)->value('company_id');
            if ($companyId && ($c = Company::find($companyId))) {
                return $c;
            }
        }

        if (
            Schema::hasTable('company_user') &&
            Schema::hasColumn('company_user', 'user_id') &&
            Schema::hasColumn('company_user', 'company_id')
        ) {
            $cid = \DB::table('company_user')->where('user_id', $userId)->value('company_id');
            if ($cid && ($c = Company::find($cid))) {
                return $c;
            }
        }

        return null;
    }
}
