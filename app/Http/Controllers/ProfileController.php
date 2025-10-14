<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * プロフィール編集
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load('profile');
        $p    = $user->profile;

        // 進捗（ざっくり10項目）
        $filled = 0; $total = 10;
        $filled += (bool)$user->name;
        $filled += (bool)$user->email;
        $filled += (bool)optional($p)->last_name;
        $filled += (bool)optional($p)->first_name;
        $filled += (bool)optional($p)->gender;
        $filled += (bool)optional($p)->prefecture;
        $filled += (bool)optional($p)->city;
        $filled += (bool)optional($p)->portfolio_url;
        $filled += (bool)optional($p)->bio;
        $filled += (bool)optional($p)->avatar_path;
        $progress = (int) round($filled / $total * 100);

        return view('profile.edit', compact('user', 'progress'));
    }

    /**
     * 更新
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // --- users（Breeze既存） ---
        $user->fill($request->only(['name', 'email']));
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        // --- profiles（1:1。無ければ作成） ---
        $profile = $user->profile()->firstOrCreate([]);

        // 画像アップロード（任意で旧画像を削除）
        if ($request->hasFile('avatar')) {
            if ($profile->avatar_path && Storage::disk('public')->exists($profile->avatar_path)) {
                Storage::disk('public')->delete($profile->avatar_path);
            }
            $profile->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        // JSON配列は空配列既定で受ける
        $educations    = $request->input('educations', []);
        $workHistories = $request->input('work_histories', []);
        $skills        = array_values(array_filter($request->input('skills', [])));

        // 希望条件：既存 desired とマージして上書き
        $existingDesired = $profile->desired ?? [];
        $incomingDesired = [
            'positions'        => array_values($request->input('desired.positions', [])),
            'employment_types' => array_values($request->input('desired.employment_types', [])),
            'locations'        => array_values($request->input('desired.locations', [])),

            // 新規：第一/第二希望 & 希望時期
            'first_choice'     => [
                'position' => $request->input('desired.first_choice.position'),
                'location' => $request->input('desired.first_choice.location'),
            ],
            'second_choice'    => [
                'position' => $request->input('desired.second_choice.position'),
                'location' => $request->input('desired.second_choice.location'),
            ],
            'hope_timing'      => $request->input('desired.hope_timing'),

            // 既存
            'salary_min'       => $request->input('desired.salary_min'),
            'available_from'   => $request->input('desired.available_from'),
            'remarks'          => $request->input('desired.remarks'),
        ];

        $profile->fill([
            // 基本/自己紹介
            'display_name'    => $request->input('display_name'),
            'bio'             => $request->input('bio'),
            'birthday'        => $request->input('birthday'),
            'gender'          => $request->input('gender'),
            'phone'           => $request->input('phone'),

            // 氏名/カナ
            'last_name'       => $request->input('last_name'),
            'first_name'      => $request->input('first_name'),
            'last_name_kana'  => $request->input('last_name_kana'),
            'first_name_kana' => $request->input('first_name_kana'),

            // 住所
            'postal_code'     => $request->input('postal_code'),
            'prefecture'      => $request->input('prefecture'),
            'city'            => $request->input('city'),
            'address1'        => $request->input('address1'),
            'address2'        => $request->input('address2'),
            'nearest_station' => $request->input('nearest_station'),
            'location'        => $request->input('location'),

            // URL / SNS
            'website_url'     => $request->input('website_url'),
            'portfolio_url'   => $request->input('portfolio_url'),
            'x_url'           => $request->input('x_url'),
            'instagram_url'   => $request->input('instagram_url'),
            'sns_x'           => $request->input('sns_x'),
            'sns_instagram'   => $request->input('sns_instagram'),

            // JSON ブロック
            'educations'      => $educations,
            'work_histories'  => $workHistories,
            'skills'          => $skills,
            'desired'         => array_replace_recursive(
                [
                    'positions'        => [],
                    'employment_types' => [],
                    'locations'        => [],
                    'first_choice'     => ['position' => null, 'location' => null],
                    'second_choice'    => ['position' => null, 'location' => null],
                    'hope_timing'      => null,
                    'salary_min'       => null,
                    'available_from'   => null,
                    'remarks'          => null,
                ],
                $existingDesired,
                $incomingDesired
            ),
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * 退会
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
