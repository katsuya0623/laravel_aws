<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $users = User::when($q, fn($query)=>
            $query->where('name','like',"%$q%")
                  ->orWhere('email','like',"%$q%")
        )->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users','q'));
    }

    public function create()
    {
        return view('admin.users.create', ['user' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['nullable','string','min:8'],
            'is_admin'  => ['nullable','boolean'],
            'is_active' => ['nullable','boolean'],
        ]);

        $user = new User();
        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password'] ?? str()->random(16));
        $user->is_admin  = (bool)($data['is_admin'] ?? false);
        $user->is_active = (bool)($data['is_active'] ?? true);
        $user->save();

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
