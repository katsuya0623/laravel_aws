<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $company = $user->companyProfile ?? new CompanyProfile(['user_id' => $user->id]);
        return view('company.edit', compact('company'));
    }

    public function update(UpdateCompanyProfileRequest $request)
    {
        $user = $request->user();
        $company = $user->companyProfile ?? new CompanyProfile(['user_id' => $user->id]);

        $data = $request->only([
            'company_name','company_name_kana','description',
            'website_url','email','tel',
            'postal_code','prefecture','city','address1','address2',
            'industry','employees','founded_on',
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('company_logos', 'public');
        }

        $company->fill($data)->save();

        return redirect()->route('user.company.edit')->with('status', '企業情報を保存しました。');
    }
}
