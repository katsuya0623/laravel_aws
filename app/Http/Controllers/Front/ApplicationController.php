<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Job;

class ApplicationController extends Controller
{
    public function store(Request $request, Job $job)
    {
        if (!$job || !$job->id) {
            abort(404, 'Job not found');
        }

        $data = $request->validate([
            'name'    => ['required','string','max:50'],
            'email'   => ['required','email'],
            'phone'   => ['nullable','string','max:50'],
            'message' => ['nullable','string'],
        ]);

        $data['job_id'] = $job->id;

        Application::create($data);

        return back()->with('status', '応募が送信されました。担当者からご連絡いたします。');
    }
}
