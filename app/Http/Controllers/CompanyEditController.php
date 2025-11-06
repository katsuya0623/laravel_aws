<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class CompanyEditController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        // 必ず既存の Company を掴む（なければ Onboarding へ）
        $company = Company::where('user_id',$user->id)->orderByDesc('id')->first();
        if (!$company) {
            return redirect()->route('onboarding.company.edit');
        }

        $profile = CompanyProfile::firstOrCreate(['company_id' => $company->id]);

        // 既存の normalize 関数を流用して OK
        return view('company.edit', ['company' => $this->normalize($profile, $company)]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $company = Company::where('user_id',$user->id)->orderByDesc('id')->first();
        if (!$company) {
            return redirect()->route('onboarding.company.edit');
        }

        $profile = CompanyProfile::firstOrCreate(['company_id' => $company->id]);

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
            'logo'               => ['nullable','file','max:10240'],
            'remove_logo'        => ['sometimes','boolean'],
        ]);

        // ここはあなたの既存 save ロジックをそのまま流用でOK（※新規 Company 作成は絶対にしない）

        // …略（すでに共有の CompanyProfileController::update と同等）

        return redirect()->route('user.company.edit')->with('status','企業情報を保存しました。');
    }

    private function normalize(CompanyProfile $p, Company $company)
    {
        // 既存の normalizeProfileForView 相当を使ってOK
        return $p;
    }
}
