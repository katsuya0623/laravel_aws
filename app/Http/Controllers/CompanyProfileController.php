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

        // ▼ company_id で必ず1行に集約して取得（user_id が違っても既存を優先）
        if ($company) {
            $profile = CompanyProfile::where('company_id', $company->id)->orderByDesc('id')->first();
            if (!$profile) {
                $profile = new CompanyProfile([
                    'company_id' => $company->id,
                    'user_id'    => $company->user_id ?? Auth::id(),
                    'company_name' => $company->name,
                ]);
            }
        } else {
            $profile = CompanyProfile::firstOrNew(['user_id' => Auth::id()]);
        }

        // 表示用にレガシー→現行→companies の順で補完
        $merged = $this->normalizeProfileForView($profile, $company);

        $role = RoleResolver::resolve(Auth::user());

        return view('company.edit', [
            'company' => $merged, // ← Bladeは $company 変数のまま
            'role'    => $role,
        ]);
    }

    /** 新規登録 or 更新（POST用） */
    public function store(Request $request) { return $this->saveProfile($request); }
    /** 更新（POST/PUT/PATCH用） */
    public function update(Request $request) { return $this->saveProfile($request); }

    /** 共通の保存処理（company_id を優先して upsert） */
    private function saveProfile(Request $request)
    {
        $data = $request->validate([
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
        ]);

        $linkedCompany = $this->resolveCompanyForUser();

        // ▼ 既存の company_id 行を最優先で取得
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

        // 会社名は企業側で変更不可
        unset($data['company_name']);

        // 値を反映
        $profile->fill($data);
        $profile->user_id = Auth::id(); // NOT NULL 保険

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

        // ロゴ差し替え
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
            \Log::warning('auto-link failed after company profile save', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
            ]);
        }

        return redirect()->route('user.company.edit')->with('status', '企業情報を保存しました。');
    }

    /** ロゴ保存 */
    private function storeLogo(UploadedFile $file): string
    {
        if (class_exists(LogoStorage::class)) {
            try {
                $stored = app(LogoStorage::class)->store($file);
                if (is_string($stored) && $stored !== '') return ltrim($stored, '/');
            } catch (\Throwable $e) {}
        }
        $dir  = 'company_logos';
        $ext  = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'svg');
        $name = now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        return $file->storeAs($dir, $name, 'public');
    }

    /** 表示用正規化：profile(現行) → profile(レガシー) → companies の順で補完 */
    private function normalizeProfileForView(CompanyProfile $profile, ?Company $company): CompanyProfile
    {
        $p = clone $profile;

        // 1) プロフィール内レガシー→現行
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

        // 2) companies からのフォールバック
        if ($company) {
            $map = [
                'description'       => ['description'],
                'website_url'       => ['website_url','site_url','url'],
                'email'             => ['email'],
                'tel'               => ['tel','phone','phone_number'],
                'postal_code'       => ['postal_code','zip'],
                'prefecture'        => ['prefecture','state'],
                'city'              => ['city'],
                'address1'          => ['address1','address_line1','street'],
                'address2'          => ['address2','address_line2'],
                'industry'          => ['industry','sector'],
                'employees'         => ['employees','employee_count'],
                'founded_on'        => ['founded_on','founded_at'],
                'logo_path'         => ['logo_path','logo','thumbnail_path'],
                'company_name'      => ['name'],
                'company_name_kana' => ['name_kana','company_kana'],
            ];

            $conn   = $company->getConnectionName() ?: config('database.default');
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

        if (Schema::hasTable('companies') && Schema::hasColumn('companies','user_id')) {
            if ($c = Company::where('user_id', $userId)->first()) return $c;
        }
        if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles','company_id')) {
            $cid = CompanyProfile::where('user_id', $userId)->value('company_id');
            if ($cid && ($c = Company::find($cid))) return $c;
        }
        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user','user_id')
            && Schema::hasColumn('company_user','company_id')) {
            $cid = \DB::table('company_user')->where('user_id', $userId)->value('company_id');
            if ($cid && ($c = Company::find($cid))) return $c;
        }
        return null;
    }
}
