<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyEditController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        $company = Company::where('user_id', $user->id)->orderByDesc('id')->first();
        if (! $company) {
            return redirect()->route('onboarding.company.edit');
        }

        $profile = CompanyProfile::firstOrCreate(['company_id' => $company->id]);

        // ✅ Blade には「Company」を渡す（中身は profile 優先に正規化）
        return view('company.edit', [
            'company' => $this->normalize($company, $profile),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $company = Company::where('user_id', $user->id)->orderByDesc('id')->first();
        if (! $company) {
            return redirect()->route('onboarding.company.edit');
        }

        $profile = CompanyProfile::firstOrCreate(['company_id' => $company->id]);

        $data = $request->validate([
            'company_name'       => ['nullable', 'string', 'max:30'], // ※disabledで送られない想定
            'company_name_kana'  => ['required', 'string', 'max:255', 'regex:/^[ァ-ヶー－\s　]+$/u'],

            // ✅ WYSIWYG は HTML が入るので 2000 だとすぐ超えがち。とりあえず上げるのが安全
            // ※「本文2000文字制限」をやりたいなら strip_tags で別途チェックする
            'description'        => ['required', 'string', 'max:20000'],

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

            'logo'               => ['nullable', 'file', 'max:10240'],
            'remove_logo'        => ['sometimes', 'boolean'],
        ]);

        // ✅ 保存先は profile に統一（Filament側も relationship('profile') で同じ場所を更新してる）
        $profile->forceFill([
            // 会社名（フロント表示）は profile を正にしたいならここに入れる
            // disabledで送られないなら companies.name から同期してOK
            'company_name'      => $company->name,

            'company_name_kana' => $data['company_name_kana'],
            'description'       => $data['description'],

            'website_url'       => $data['website_url'] ?? null,
            'email'             => $data['email'] ?? null,
            'tel'               => $data['tel'] ?? null,

            'postal_code'       => $data['postal_code'],
            'prefecture'        => $data['prefecture'],
            'city'              => $data['city'],
            'address1'          => $data['address1'],
            'address2'          => $data['address2'] ?? null,

            'industry'          => $data['industry'],
            'employees'         => $data['employees'],
            'founded_on'        => $data['founded_on'] ?? null,
        ]);

        // ロゴ削除
        if ($request->boolean('remove_logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $profile->logo_path = null;
        }

        // ロゴ更新
        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $profile->logo_path = $request->file('logo')->store('company_logos', 'public');
        }

        $profile->save();

        return redirect()->route('user.company.edit')->with('status', '企業情報を保存しました。');
    }

    private function normalize(Company $company, CompanyProfile $profile): Company
    {
        // ✅ Bladeが $company->xxx 参照してもズレないよう、表示用に上書き（保存はしない）
        $company->setAttribute('company_name_kana', $profile->company_name_kana);
        $company->setAttribute('description', $profile->description);
        $company->setAttribute('website_url', $profile->website_url);
        $company->setAttribute('email', $profile->email);
        $company->setAttribute('tel', $profile->tel);
        $company->setAttribute('postal_code', $profile->postal_code);
        $company->setAttribute('prefecture', $profile->prefecture);
        $company->setAttribute('city', $profile->city);
        $company->setAttribute('address1', $profile->address1);
        $company->setAttribute('address2', $profile->address2);
        $company->setAttribute('industry', $profile->industry);
        $company->setAttribute('employees', $profile->employees);
        $company->setAttribute('founded_on', $profile->founded_on);
        $company->setAttribute('logo_path', $profile->logo_path);

        // ついでに relation も載せておく（詳細ページで $company->profile を使える）
        $company->setRelation('profile', $profile);

        return $company;
    }
}
