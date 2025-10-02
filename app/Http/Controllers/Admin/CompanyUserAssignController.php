<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CompanyUserAssignController extends Controller
{
    /** 割り振り画面 */
    public function edit(CompanyProfile $company)
    {
        $assigned = $company->users()->orderBy('email')->get();

        // 候補（company / enduser）
        $candidates = User::query()
            ->whereIn('role', ['company', 'enduser'])
            ->orderBy('email')
            ->limit(200)
            ->get();

        return view('admin.companies.assign-user', compact('company','assigned','candidates'));
    }

    /** 既存ユーザーを割り振り（複数可） */
    public function assignExisting(Request $request, CompanyProfile $company)
    {
        $data = $request->validate([
            'user_id' => ['required','integer', Rule::exists('users','id')],
        ]);

        $user = User::findOrFail($data['user_id']);

        if (($user->role ?? null) === 'admin') {
            return back()->withErrors(['user_id' => '管理者アカウントは割り振りできません。']);
        }

        if (($user->role ?? 'enduser') !== 'company') {
            $user->role = 'company';
            $user->save();
        }

        $company->users()->syncWithoutDetaching([$user->id]);

        return back()->with('status', "割り振りました：{$user->email}");
    }

    /** 新規ユーザー作成→割り振り */
    public function createAndAssign(Request $request, CompanyProfile $company)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')],
            'password' => ['nullable','string','min:8'],
        ]);

        $password = $data['password'] ?: str()->password(12);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($password),
            'role'     => 'company',
        ]);

        $company->users()->syncWithoutDetaching([$user->id]);

        // 代表にしたい時はコメント解除
        // $company->setPrimaryUser($user);

        return back()->with('status', "新規作成して割り振りました：{$user->email}");
    }

    /** 解除（多対多から外す。代表なら外す） */
    public function unassign(CompanyProfile $company, User $user)
    {
        $company->users()->detach($user->id);

        if ($company->user && $company->user->id === $user->id) {
            $company->setPrimaryUser(null);
        }

        return back()->with('status', "解除しました：{$user->email}");
    }

    /** 代表担当者に設定（互換列へミラー） */
    public function setPrimary(CompanyProfile $company, User $user)
    {
        if (!$company->users()->where('users.id', $user->id)->exists()) {
            return back()->withErrors(['user' => 'このユーザーはこの会社に割り振られていません。']);
        }
        $company->setPrimaryUser($user);
        return back()->with('status', "代表担当者を設定しました：{$user->email}");
    }
}
