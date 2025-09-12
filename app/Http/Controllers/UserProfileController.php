<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserProfileRequest;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);
        return view('profile.edit', compact('user','profile'));
    }

    public function update(UpdateUserProfileRequest $request)
    {
        $user = $request->user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

        $data = $request->only([
            'display_name','bio','website_url','x_url','instagram_url','location','birthday'
        ]);

        // アバターアップロード
        if ($request->hasFile('avatar')) {
            if ($profile->avatar_path) {
                Storage::disk('public')->delete($profile->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $profile->fill($data);
        $profile->save();

        return redirect()->route('user.profile.edit')->with('status','プロフィールを保存しました。');
    }
}
