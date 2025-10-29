<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Models\WorkHistory;

class EndUserProfileController extends Controller
{
    public function show(User $user)
    {
        // プロフィールがまだ無い場合でも空で表示できるように
        $profile = Profile::firstOrNew(['user_id' => $user->id]);

        // 職歴
        $workHistories = WorkHistory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('start_date')
            ->get();

        // 必要なら今後、学歴などもここで取得して渡す
        // $educations = Education::where('user_id', $user->id)->orderByDesc('start_date')->get();

        return view('admin.users.profile-show', [
            'user'          => $user,
            'profile'       => $profile,
            'workHistories' => $workHistories,
        ]);
    }
}
