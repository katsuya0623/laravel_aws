<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RecruitJob; // ← RecruitJob モデルを使用する場合（まだ無ければコメントアウト可）

class JobController extends Controller
{
    /**
     * 求人作成フォーム表示
     */
    public function create()
    {
        return view('front.jobs.create');
    }

    /**
     * 求人新規登録処理
     */
    public function store(Request $request)
    {
        // バリデーション（入力値の確認）
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'summary'        => 'nullable|string|max:1000',
            'body'           => 'required|string',
            'location'       => 'nullable|string|max:255',
            'employment_type'=> 'nullable|string|max:100',
            'work_style'     => 'nullable|string|max:100',
            'salary_unit'    => 'nullable|string|max:50',
            'tags'           => 'nullable|string|max:255',
            'status'         => 'nullable|in:draft,public,closed',
            'publish_at'     => 'nullable|date',
            'slug'           => 'nullable|string|max:255',
        ]);

        // ▼ DB登録（まだ RecruitJob モデルを作っていないならコメントアウトでもOK）
        // RecruitJob::create($validated);

        // ▼ 保存後の動作：今回はデモ的に完了メッセージを返す
        return redirect()
            ->route('recruit_jobs.create')
            ->with('success', '求人を保存しました！');
    }
}
