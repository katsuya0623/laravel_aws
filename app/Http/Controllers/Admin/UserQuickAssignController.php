<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserQuickAssignController extends Controller
{
    public function setRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['enduser','company'])],
        ]);
        if (($user->role ?? 'enduser') !== $data['role']) {
            $user->role = $data['role'];
            $user->save();
        }
        return back()->with('status', "役割を {$data['role']} に更新しました（{$user->email}）");
    }

    public function assignCompany(Request $request, User $user)
    {
        $data = $request->validate([
            'company_profile_id' => ['required','integer', Rule::exists('company_profiles','id')],
            'set_primary'        => ['sometimes','boolean'],
        ]);

        if (($user->role ?? null) === 'admin') {
            return back()->withErrors(['user' => '管理者アカウントは割り振りできません。']);
        }
        if (($user->role ?? 'enduser') !== 'company') {
            $user->role = 'company';
            $user->save();
        }

        $company = CompanyProfile::findOrFail($data['company_profile_id']);
        $company->users()->syncWithoutDetaching([$user->id]);

        if ($request->boolean('set_primary') && method_exists($company,'setPrimaryUser')) {
            $company->setPrimaryUser($user);
        }

        return back()->with('status', "企業へ割り振りました：{$company->company_name} ← {$user->email}");
    }

    public function unassignCompany(User $user, CompanyProfile $company)
    {
        $company->users()->detach($user->id);

        if ($company->user && $company->user->id === $user->id) {
            $company->setPrimaryUser(null);
        }

        return back()->with('status', "企業割り振りを解除しました：{$company->company_name} ← {$user->email}");
    }
}
