<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');

        $users = User::with('companyProfiles')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // 行内の「会社割当」用セレクトに使用
        $companies = CompanyProfile::orderBy('company_name')->get(['id','company_name']);

        return view('admin.users.index', compact('users','q','companies'));
    }

    public function create()
    {
        // 作成画面の「初回会社割当」セレクト用
        $companies = CompanyProfile::orderBy('company_name')->get(['id','company_name']);
        return view('admin.users.create', ['user' => new User(), 'companies' => $companies]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['nullable','string','min:8'],
            'is_admin'  => ['nullable','boolean'],
            'is_active' => ['nullable','boolean'],

            // 追加：役割 & 初回会社割当
            'role'               => ['nullable', Rule::in(['enduser','company'])],
            'company_profile_id' => ['nullable','integer', Rule::exists('company_profiles','id')],
            'set_primary'        => ['nullable','boolean'],
        ]);

        $user = new User();
        $user->name      = $data['name'];
        $user->email     = $data['email'];
        $user->password  = Hash::make($data['password'] ?? str()->random(16));
        $user->is_admin  = (bool)($data['is_admin'] ?? false);
        $user->is_active = (bool)($data['is_active'] ?? true);

        // 追加：role（カラムがある前提）
        $user->role = $data['role'] ?? 'enduser';

        $user->save();

        // 追加：初回会社割当（任意）
        if (!empty($data['company_profile_id'])) {
            $company = CompanyProfile::find($data['company_profile_id']);
            if ($company) {
                // 会社割当するなら、roleをcompanyに揃える（念のため）
                if (($user->role ?? 'enduser') !== 'company') {
                    $user->role = 'company';
                    $user->save();
                }
                $company->users()->syncWithoutDetaching([$user->id]);

                if (!empty($data['set_primary'])) {
                    // 代表に設定（互換：company_profiles.user_id へミラー）
                    if (method_exists($company, 'setPrimaryUser')) {
                        $company->setPrimaryUser($user);
                    } else {
                        // 互換メソッドが無い場合は直接代入（あれば）
                        if (\Schema::hasColumn('company_profiles', 'user_id')) {
                            $company->user_id = $user->id;
                            $company->save();
                        }
                    }
                }
            }
        }

        return redirect()->route('admin.users.edit', $user)->with('status','作成しました');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable','string','min:8'],
            'is_admin'  => ['required','boolean'],
            'is_active' => ['required','boolean'],
            // （必要なら role もここで更新可だが、行内操作に任せるなら省略）
        ]);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->name      = $data['name'];
        $user->email     = $data['email'];
        $user->is_admin  = (bool)$data['is_admin'];
        $user->is_active = (bool)$data['is_active'];
        $user->save();

        return redirect()->route('admin.users.edit', $user)->with('status','更新しました');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('status','自分自身は削除できません');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('status','削除しました');
    }
}
