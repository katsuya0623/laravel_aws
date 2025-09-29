<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\LogoStorage; // あれば使う（無くてもOK）

class CompanyController extends Controller
{
    /** 一覧 */
    public function index()
    {
        $companies = CompanyProfile::orderByDesc('updated_at')->paginate(20);
        return view('admin.companies.index', compact('companies'));
    }

    /** 作成フォーム */
    public function create()
    {
        $company = new CompanyProfile();
        return view('admin.companies.create', compact('company'));
    }

    /** 登録 */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $company = new CompanyProfile();
        $company->fill($data);

        // ロゴ保存 → DBは logo_path に保存
        if ($request->hasFile('logo')) {
            $company->logo_path = $this->storeLogoFile($request->file('logo'));
        }

        $company->save();

        return redirect()
            ->route('admin.companies.edit', $company->id)
            ->with('status', '企業情報を登録しました。');
    }

    /** 編集フォーム */
    public function edit(int $id)
    {
        $company = CompanyProfile::findOrFail($id);
        return view('admin.companies.edit', compact('company'));
    }

    /** 更新 */
    public function update(Request $request, int $id)
    {
        $company = CompanyProfile::findOrFail($id);
        $data    = $this->validated($request);

        $company->fill($data);

        // 明示削除（name="remove_logo" value="1"）
        if ($request->boolean('remove_logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $company->logo_path = null;
        }

        // 新規アップで差し替え
        if ($request->hasFile('logo')) {
            $newPath = $this->storeLogoFile($request->file('logo'));
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $company->logo_path = $newPath;
        }

        $company->save();

        return back()->with('status', '企業情報を更新しました。');
    }

    /** 削除 */
    public function destroy(int $id)
    {
        $company = CompanyProfile::findOrFail($id);

        if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
            Storage::disk('public')->delete($company->logo_path);
        }
        $company->delete();

        return redirect()->route('admin.companies.index')->with('status', '企業情報を削除しました。');
    }

    /**
     * 共通バリデーション
     * - 入力名は CompanyProfile のカラムに合わせる
     * - ロゴは input name="logo"（DBは logo_path に保存）
     */
    private function validated(Request $request): array
    {
        return $request->validate([
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

            // アップロード（input name="logo"）
            'logo' => [
                'nullable','file','max:10240', // 10MB
                'mimes:jpg,jpeg,png,webp,svg,svgz',
                'mimetypes:image/jpeg,image/png,image/webp,image/svg+xml,application/xml,text/xml',
            ],
            'remove_logo' => ['sometimes','boolean'],
        ]);
    }

    /**
     * ロゴ保存処理
     * - 可能なら App\Services\LogoStorage を使用
     * - 無い場合は public ディスク (storage/app/public/company/logos) へ保存
     * - 返り値は「相対パス」（例: company/logos/xxxx.svg）
     */
    private function storeLogoFile(UploadedFile $file): string
    {
        // 1) サービスがあれば優先
        if (class_exists(LogoStorage::class)) {
            try {
                $uploader = app(LogoStorage::class);
                $stored   = $uploader->store($file);
                if (is_string($stored) && $stored !== '') {
                    return ltrim($stored, '/'); // 念のため先頭スラッシュ除去
                }
            } catch (\Throwable $e) {
                // サービス失敗時はフォールバックへ
            }
        }

        // 2) フォールバック保存
        $dir  = 'company/logos';
        $ext  = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'svg');
        $name = now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        // storage/app/public/company/logos/...
        return $file->storeAs($dir, $name, 'public'); // 相対パスを返す
    }
}
