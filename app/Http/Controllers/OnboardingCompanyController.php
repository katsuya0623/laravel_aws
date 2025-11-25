<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnboardingCompanyController extends Controller
{
    public function create()
    {
        $user = Auth::user();

        // 既に Company が紐づいていれば編集へ
        $existing = Company::where('user_id', $user->id)->orderByDesc('id')->first();
        if ($existing) {
            return redirect()->route('user.company.edit');
        }

        return view('company.onboarding');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // 既に company があればオンボーディングではなく編集画面へ
        $existing = Company::where('user_id', $user->id)->first();
        if ($existing) {
            return redirect()->route('user.company.edit');
        }

        // フォームと同じ必須 + ロゴ(任意)
        $data = $request->validate([
            'company_name'       => ['nullable', 'string', 'max:30'],
            'company_name_kana'  => ['required', 'string', 'max:255', 'regex:/^[ァ-ヶー－\s　]+$/u'],
            'description'        => ['required', 'string', 'max:2000'],
            'postal_code'        => ['required', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture'         => ['required', 'string', 'max:255'],
            'city'               => ['required', 'string', 'max:255'],
            'address1'           => ['required', 'string', 'max:255'],
            'industry'           => ['required', 'string', 'max:255'],
            'employees'          => ['required', 'integer', 'min:1', 'max:1000000'],
            'logo'               => ['nullable', 'file', 'max:10240', 'mimetypes:image/svg+xml,image/png,image/jpeg,image/webp'],
        ]);

        DB::transaction(function () use ($user, $data, $request) {
            // --- Company 既存取得 or 作成 --- //
            $name = $data['company_name'] ?? ($user->company_name ?? $user->name ?? '未設定の会社');
            $name = Str::limit(trim($name) !== '' ? $name : '未設定の会社', 30, '');

            // ベースとなる slug（会社名から生成）
            $baseSlug = Str::slug($name) ?: 'company-' . Str::lower(Str::random(6));

            // 1) user_id で既存 company を探す
            $company = Company::where('user_id', $user->id)->first();

            // 2) 見つからなければ、slug で既存 company（招待時に作成されたもの）を拾う
            if (! $company && Schema::hasColumn('companies', 'slug')) {
                $company = Company::where('slug', $baseSlug)->first();
            }

            if (! $company) {
                // 3) どちらも見つからなければ、新規 company 作成（slug はユニーク生成）
                $company = new Company();

                if (Schema::hasColumn('companies', 'name')) {
                    $company->name = $name;
                }

                if (Schema::hasColumn('companies', 'user_id')) {
                    $company->user_id = $user->id;
                }

                if (Schema::hasColumn('companies', 'slug')) {
                    $company->slug = $this->generateUniqueCompanySlug($baseSlug);
                }

                // ステータス系の初期値をできるだけ埋める
                foreach (['status', 'is_public', 'is_published', 'published'] as $col) {
                    if (Schema::hasColumn('companies', $col) && empty($company->{$col})) {
                        $company->{$col} = $col === 'status' ? 'draft' : 0;
                    }
                }
            } else {
                // 既存 company がある場合は基本情報だけ更新（slug は変更しない）
                if (Schema::hasColumn('companies', 'name')) {
                    $company->name = $name;
                }

                if (Schema::hasColumn('companies', 'user_id') && empty($company->user_id)) {
                    $company->user_id = $user->id;
                }
            }

            $company->save();

            // --- CompanyProfile 1:1 作成 or 更新 --- //
            $profileKeys = ['company_id' => $company->id];
            if (Schema::hasColumn('company_profiles', 'user_id')) {
                $profileKeys['user_id'] = $user->id;
            }

            // プロフィールに入れる値
            $put = [
                'company_name'      => $company->name,
                'company_name_kana' => $data['company_name_kana'],
                'description'       => $data['description'],
                'postal_code'       => preg_replace('/^(\d{3})-?(\d{4})$/', '$1-$2', $data['postal_code']), // 123-4567 に正規化
                'prefecture'        => $data['prefecture'],
                'city'              => $data['city'],
                'address1'          => $data['address1'],
                'industry'          => $data['industry'],
            ];

            $profileValues = [];

            foreach ($put as $k => $v) {
                if (Schema::hasColumn('company_profiles', $k)) {
                    $profileValues[$k] = $v;
                }
            }

            // 従業員数（両候補対応）
            if (Schema::hasColumn('company_profiles', 'employees_count')) {
                $profileValues['employees_count'] = $data['employees'];
            } elseif (Schema::hasColumn('company_profiles', 'employees')) {
                $profileValues['employees'] = $data['employees'];
            }

            /** @var CompanyProfile $p */
            $p = CompanyProfile::updateOrCreate($profileKeys, $profileValues);

            // --- ロゴ保存（任意） --- //
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $f = $request->file('logo');

                $dir = 'company_logos/' . now()->format('Ymd');
                $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension());
                if ($ext === 'svgz') {
                    $ext = 'svg';
                }

                $path = $f->storeAs($dir, Str::random(20) . '.' . $ext, 'public');

                // profile 側に保存（列があれば）
                if (Schema::hasColumn('company_profiles', 'logo_path')) {
                    $p->logo_path = $path; // 表示は Storage::url($path)
                }

                // company 側にも互換で入れる（列があれば）
                if (Schema::hasColumn($company->getTable(), 'logo_path')) {
                    $company->logo_path = $path;
                    $company->save();
                }

                $p->save();
            }

            // 入力が満たされていれば is_completed を立てる
            $this->markCompletedIfReady($p);
        });

        return redirect()
            ->route('user.company.edit')
            ->with('status', '企業情報を作成しました。');
    }

    /**
     * 必須入力が満たされていれば is_completed を 1 にする
     */
    private function markCompletedIfReady(CompanyProfile $p): void
    {
        // 充足チェック（存在する列だけ判定）
        $kanaOk = Schema::hasColumn('company_profiles', 'company_name_kana')
            ? filled($p->company_name_kana)
            : (Schema::hasColumn('company_profiles', 'kana') ? filled($p->kana) : true);

        $ok =
            $kanaOk &&
            (!Schema::hasColumn('company_profiles', 'description') || filled($p->description)) &&
            (!Schema::hasColumn('company_profiles', 'postal_code') || filled($p->postal_code)) &&
            (!Schema::hasColumn('company_profiles', 'prefecture') || filled($p->prefecture)) &&
            (!Schema::hasColumn('company_profiles', 'city') || filled($p->city)) &&
            (!Schema::hasColumn('company_profiles', 'address1') || filled($p->address1)) &&
            (!Schema::hasColumn('company_profiles', 'industry') || filled($p->industry)) &&
            (
                (Schema::hasColumn('company_profiles', 'employees_count') && filled($p->employees_count)) ||
                (Schema::hasColumn('company_profiles', 'employees') && filled($p->employees)) ||
                (!Schema::hasColumn('company_profiles', 'employees_count') && !Schema::hasColumn('company_profiles', 'employees'))
            );

        if (Schema::hasColumn('company_profiles', 'is_completed')) {
            $p->is_completed = $ok ? 1 : 0; // SQLiteでも確実に 0/1
            $p->save();
        }
    }

    /**
     * companies.slug に対してユニークなスラッグを発行する
     */
    private function generateUniqueCompanySlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $i    = 1;

        if (! Schema::hasColumn('companies', 'slug')) {
            // 念のため slug カラムが無い場合はそのまま返す
            return $slug;
        }

        while (Company::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
