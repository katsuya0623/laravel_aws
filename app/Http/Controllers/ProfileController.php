<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Arr;

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
     * 互換：単一update（既存導線のため残置）
     * 今後は分割エンドポイントを利用してください。
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
            'first_choice'     => [
                'position' => $request->input('desired.first_choice.position'),
                'location' => $request->input('desired.first_choice.location'),
            ],
            'second_choice'    => [
                'position' => $request->input('desired.second_choice.position'),
                'location' => $request->input('desired.second_choice.location'),
            ],
            'hope_timing'      => $request->input('desired.hope_timing'),
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
     * ===== ここから分割更新メソッド =====
     */

    /** 基本情報のみ更新（他のJSONは触らない） */
    public function updateBasics(Request $request): RedirectResponse
    {
        $user = $request->user();

        // usersテーブル
        $validatedUser = $request->validate([
            'name'  => ['required','string','max:255'],
            'email' => ['required','email','max:255'],
        ]);
        $user->fill($validatedUser);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        // profileテーブル
        $validated = $request->validate([
            'last_name'        => ['nullable','string','max:255'],
            'first_name'       => ['nullable','string','max:255'],
            'last_name_kana'   => ['nullable','string','max:255'],
            'first_name_kana'  => ['nullable','string','max:255'],
            'gender'           => ['nullable','in:no_answer,male,female,other'],
            'birthday'         => ['nullable','date'],
            'phone'            => ['nullable','string','max:255'],
            'postal_code'      => ['nullable','string','max:20'],
            'prefecture'       => ['nullable','string','max:20'],
            'city'             => ['nullable','string','max:255'],
            'address1'         => ['nullable','string','max:255'],
            'address2'         => ['nullable','string','max:255'],
            'nearest_station'  => ['nullable','string','max:255'],
            'portfolio_url'    => ['nullable','url','max:255'],
            'website_url'      => ['nullable','url','max:255'],
            'sns_x'            => ['nullable','string','max:255'],
            'sns_instagram'    => ['nullable','string','max:255'],
            'bio'              => ['nullable','string'],
            'avatar'           => ['nullable','image','max:5120'],
        ]);

        $profile = $user->profile()->firstOrCreate([]);
        $profile->fill(Arr::except($validated, ['avatar']));

        if ($request->hasFile('avatar')) {
            if ($profile->avatar_path && Storage::disk('public')->exists($profile->avatar_path)) {
                Storage::disk('public')->delete($profile->avatar_path);
            }
            $profile->avatar_path = $request->file('avatar')->store('avatars','public');
        }

        $profile->save();

        return back()->with('status','profile-updated');
    }

    /** 学歴のみ更新（他は触らない） */
    public function updateEducations(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'educations'                  => ['required','array'],
            'educations.*.school'         => ['nullable','string','max:255'],
            'educations.*.faculty'        => ['nullable','string','max:255'],
            'educations.*.department'     => ['nullable','string','max:255'],
            'educations.*.status'         => ['nullable','string','max:50'],
            'educations.*.period_from'    => ['nullable','date'],
            'educations.*.period_to'      => ['nullable','date'],
        ]);

        $profile = $request->user()->profile()->firstOrCreate([]);
        $profile->educations = array_values($validated['educations']);
        $profile->save();

        return back()->with('status','profile-updated');
    }

    /** 職歴のみ更新（他は触らない） */
    public function updateWorks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'work_histories'                    => ['required','array'],
            'work_histories.*.company'          => ['nullable','string','max:255'],
            'work_histories.*.from'             => ['nullable','date'],
            'work_histories.*.to'               => ['nullable','date'],
            'work_histories.*.employment_type'  => ['nullable','string','max:100'],
            'work_histories.*.dept'             => ['nullable','string','max:255'],
            'work_histories.*.position'         => ['nullable','string','max:255'],
            'work_histories.*.tasks'            => ['nullable','string'],
            'work_histories.*.achievements'     => ['nullable','string'],
        ]);

        $profile = $request->user()->profile()->firstOrCreate([]);
        $profile->work_histories = array_values($validated['work_histories']);
        $profile->save();

        return back()->with('status','profile-updated');
    }

    /** 希望条件のみ更新（他は触らない） */
    public function updateDesired(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'desired'                           => ['required','array'],
            'desired.positions'                 => ['nullable','array'],
            'desired.locations'                 => ['nullable','array'],
            'desired.employment_types'          => ['nullable','array'],
            'desired.first_choice.position'     => ['nullable','string','max:100'],
            'desired.first_choice.location'     => ['nullable','string','max:100'],
            'desired.second_choice.position'    => ['nullable','string','max:100'],
            'desired.second_choice.location'    => ['nullable','string','max:100'],
            'desired.hope_timing'               => ['nullable','string','max:50'],
            'desired.available_from'            => ['nullable','date'],
            'desired.salary_min'                => ['nullable','integer','min:0'],
            'desired.remarks'                   => ['nullable','string'],
        ]);

        $profile = $request->user()->profile()->firstOrCreate([]);
        // 空配列上書きも許可したい場合は hidden ダミーをBlade側に置いてキー送信させる
        $profile->desired = $validated['desired'] ?? [];
        $profile->save();

        return back()->with('status','profile-updated');
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
