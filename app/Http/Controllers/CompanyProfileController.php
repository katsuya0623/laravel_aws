<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Services\LogoStorage;

class CompanyProfileController extends Controller
{
    /** 編集画面 */
    public function edit()
    {
        $company = CompanyProfile::firstOrNew(['user_id' => Auth::id()]);
        return view('company.edit', compact('company'));
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
        // ★ CompanyProfile のカラム名に合わせる（employees は整数）
        $data = $request->validate([
            'company_name'       => ['nullable','string','max:255'],
            'company_name_kana'  => ['nullable','string','max:255'],
            'description'        => ['nullable','string'],
            'website_url'        => ['nullable','string','max:255'],
            'email'              => ['nullable','string','max:255'],
            'tel'                => ['nullable','string','max:255'],
            'postal_code'        => ['nullable','string','max:20'],
            'prefecture'         => ['nullable','string','max:255'],
            'city'               => ['nullable','string','max:255'],
            'address1'           => ['nullable','string','max:255'],
            'address2'           => ['nullable','string','max:255'],
            'industry'           => ['nullable','string','max:255'],
            'employees'          => ['nullable','integer'],
            'founded_on'         => ['nullable','date'],

            // 画像（10MB / SVG可）
            'logo' => [
                'nullable','file','max:10240',
                'mimes:jpg,jpeg,png,webp,svg,svgz',
                'mimetypes:image/jpeg,image/png,image/webp,image/svg+xml,application/xml,text/xml',
            ],
            'remove_logo' => ['sometimes','boolean'],
        ]);

        // ユーザーごとのレコードを取得 or 新規作成
        $company = CompanyProfile::firstOrNew(['user_id' => Auth::id()]);
        $company->fill($data);
        $company->user_id = Auth::id();

        // 明示削除
        if ($request->boolean('remove_logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $company->logo_path = null;
        }

        // アップロード時は差し替え
        if ($request->hasFile('logo')) {
            $newPath = $this->storeLogo($request->file('logo'));

            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $company->logo_path = $newPath; // ★ DBは logo_path に保存
        }

        $company->save();

        return redirect()->route('user.company.edit')->with('status', '企業情報を保存しました。');
    }

    /**
     * ロゴ保存
     * - 可能なら App\Services\LogoStorage を利用
     * - 無い or 失敗時は storage/app/public/company_logos へ保存
     * - 返り値は相対パス（例: company_logos/xxxx.svg）
     */
    private function storeLogo(UploadedFile $file): string
    {
        // 1) サービスがあれば優先
        if (class_exists(LogoStorage::class)) {
            try {
                $stored = app(LogoStorage::class)->store($file);
                if (is_string($stored) && $stored !== '') {
                    return ltrim($stored, '/'); // 念のため先頭スラッシュ除去
                }
            } catch (\Throwable $e) {
                // 失敗時はフォールバックへ
            }
        }

        // 2) フォールバック保存
        $dir  = 'company_logos'; // 既存データに合わせて下線区切り
        $ext  = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'svg');
        $name = now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        return $file->storeAs($dir, $name, 'public'); // → company_logos/xxxx.svg
    }
}
