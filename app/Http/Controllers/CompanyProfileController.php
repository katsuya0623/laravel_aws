<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\LogoStorage;
// ★ 追加
use App\Support\RoleResolver;

class CompanyProfileController extends Controller
{
    /** 編集画面 */
    public function edit()
    {
        $company = CompanyProfile::firstOrNew(['user_id' => Auth::id()]);

        // ★ 追加：レイアウトに渡すロール
        $role = RoleResolver::resolve(Auth::user());

        return view('company.edit', compact('company', 'role')); // ← ここで渡す
    }

    /** 新規登録 or 更新（POST用） */
    public function store(Request $request)
    {
        return $this->saveProfile($request);
    }

    /** 更新（PUT/PATCH用） */
    public function update(Request $request)
    {
        return $this->saveProfile($request);
    }

    /** 共通の保存処理 */
    private function saveProfile(Request $request)
    {
        // 会社名はフォームに来ても後で必ず無視（企業側は変更不可）
        $data = $request->validate(
            [
                'company_name'       => ['nullable', 'string', 'max:30'],
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
            ],
            [
                'company_name'      => '会社名',
                'company_name_kana' => '会社名（カナ）',
                'description'       => '事業内容 / 紹介',
                'website_url'       => 'Webサイト',
                'email'             => '代表メール',
                'tel'               => '電話番号',
                'postal_code'       => '郵便番号',
                'prefecture'        => '都道府県',
                'city'              => '市区町村',
                'address1'          => '番地・建物',
                'address2'          => '部屋番号など',
                'industry'          => '業種',
                'employees'         => '従業員数',
                'founded_on'        => '設立日',
                'logo'              => 'ロゴ画像',
            ]
        );

        $company = CompanyProfile::firstOrNew(['user_id' => Auth::id()]);

        unset($data['company_name']); // 企業側は変更不可

        $company->fill($data);
        $company->user_id = Auth::id();

        if ($request->boolean('remove_logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $company->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            $newPath = $this->storeLogo($request->file('logo'));
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $company->logo_path = $newPath;
        }

        $company->save();

        if (method_exists($company, 'syncCompletionFlags')) {
            $company->syncCompletionFlags();
        }

        return redirect()->route('user.company.edit')->with('status', '企業情報を保存しました。');
    }

    private function storeLogo(UploadedFile $file): string
    {
        if (class_exists(LogoStorage::class)) {
            try {
                $stored = app(LogoStorage::class)->store($file);
                if (is_string($stored) && $stored !== '') {
                    return ltrim($stored, '/');
                }
            } catch (\Throwable $e) {}
        }

        $dir  = 'company_logos';
        $ext  = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'svg');
        $name = now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        return $file->storeAs($dir, $name, 'public');
    }
}
