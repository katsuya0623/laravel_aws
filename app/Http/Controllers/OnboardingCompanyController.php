<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        // 二重作成ガード
        $existing = Company::where('user_id', $user->id)->first();
        if ($existing) {
            return redirect()->route('user.company.edit');
        }

        // フォームと同じ必須
        $data = $request->validate([
            'company_name'       => ['nullable','string','max:30'],
            'company_name_kana'  => ['required','string','max:255','regex:/^[ァ-ヶー－\s　]+$/u'],
            'description'        => ['required','string','max:2000'],
            'postal_code'        => ['required','regex:/^\d{3}-?\d{4}$/'],
            'prefecture'         => ['required','string','max:255'],
            'city'               => ['required','string','max:255'],
            'address1'           => ['required','string','max:255'],
            'industry'           => ['required','string','max:255'],
            'employees'          => ['required','integer','min:1','max:1000000'],
        ]);

        DB::transaction(function () use ($user, $data) {
            // --- Company 作成 ---
            $name = $data['company_name'] ?? ($user->company_name ?? $user->name ?? '未設定の会社');
            $name = Str::limit(trim($name) !== '' ? $name : '未設定の会社', 30, '');

            $company = new Company();
            if (Schema::hasColumn('companies', 'name'))    $company->name = $name;
            if (Schema::hasColumn('companies', 'user_id')) $company->user_id = $user->id;
            if (Schema::hasColumn('companies', 'slug'))    $company->slug = Str::slug($name) ?: 'company-'.Str::lower(Str::random(6));
            foreach (['status','is_public','is_published','published'] as $col) {
                if (Schema::hasColumn('companies', $col) && empty($company->{$col})) {
                    $company->{$col} = $col === 'status' ? 'draft' : 0;
                }
            }
            $company->save();

            // --- CompanyProfile 1:1 作成 ---
            $p = new CompanyProfile();
            if (Schema::hasColumn('company_profiles', 'company_id')) $p->company_id = $company->id;
            if (Schema::hasColumn('company_profiles', 'user_id'))    $p->user_id    = $user->id;

            // 列があるものだけ詰める
            $put = [
                'company_name'      => $company->name,
                'company_name_kana' => $data['company_name_kana'],
                'description'       => $data['description'],
                'postal_code'       => $data['postal_code'],
                'prefecture'        => $data['prefecture'],
                'city'              => $data['city'],
                'address1'          => $data['address1'],
                'industry'          => $data['industry'],
            ];
            foreach ($put as $k => $v) {
                if (Schema::hasColumn('company_profiles', $k)) {
                    $p->{$k} = $v;
                }
            }
            // 従業員数（両候補対応）
            if (Schema::hasColumn('company_profiles', 'employees_count')) {
                $p->employees_count = $data['employees'];
            } elseif (Schema::hasColumn('company_profiles', 'employees')) {
                $p->employees = $data['employees'];
            }

            $p->save();

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
        $kanaOk   = Schema::hasColumn('company_profiles','company_name_kana') ? filled($p->company_name_kana) :
                    (Schema::hasColumn('company_profiles','kana') ? filled($p->kana) : true);

        $ok =
            $kanaOk &&
            (!Schema::hasColumn('company_profiles','description') || filled($p->description)) &&
            (!Schema::hasColumn('company_profiles','postal_code') || filled($p->postal_code)) &&
            (!Schema::hasColumn('company_profiles','prefecture')  || filled($p->prefecture)) &&
            (!Schema::hasColumn('company_profiles','city')        || filled($p->city)) &&
            (!Schema::hasColumn('company_profiles','address1')    || filled($p->address1)) &&
            (!Schema::hasColumn('company_profiles','industry')    || filled($p->industry)) &&
            (
                (Schema::hasColumn('company_profiles','employees_count') && filled($p->employees_count)) ||
                (Schema::hasColumn('company_profiles','employees')       && filled($p->employees)) ||
                (!Schema::hasColumn('company_profiles','employees_count') && !Schema::hasColumn('company_profiles','employees')) // どちらの列も無い環境はスキップ
            );

        if (Schema::hasColumn('company_profiles', 'is_completed')) {
            $p->is_completed = $ok ? 1 : 0; // SQLiteでも確実に 0/1
            $p->save();
        }
    }
}
