<?php

// app/Http/Controllers/Admin/CompanyInvitationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Notifications\CompanyInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
// ★ 追加
use Illuminate\Support\Facades\Notification;

class CompanyInvitationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email'        => ['required', 'email'],
            'company_name' => ['required', 'string', 'max:255'],
        ]);

        // ── ① slug を安全に生成（日本語名でも必ず値が入るように）
        $base = Str::slug($data['company_name']); // 日本語だと空になりやすい
        $slug = $base !== '' ? $base : 'c-' . Str::lower(Str::random(8));

        // 重複回避ループ
        $try = 0;
        while (Company::where('slug', $slug)->exists()) {
            $try++;
            $slug = ($base !== '' ? $base : 'c') . '-' . ($try === 1 ? Str::lower(Str::random(6)) : $try);
        }

        // ── ② Company を最小項目で作成（slug 必須を満たす）
        $company = Company::create([
            'name' => $data['company_name'],
            'slug' => $slug,
        ]);

        // ── ③ 招待レコード作成
        $expiresDays = (int) config('app.invitation_days', 7);

        $invitation = CompanyInvitation::create([
            'email'        => $data['email'],
            'company_name' => $data['company_name'],
            'company_id'   => $company->id,
            'token'        => (string) Str::uuid(),
            'expires_at'   => now()->addDays($expiresDays),
            'status'       => 'pending',
            'invited_by'   => $request->user()?->id,
        ]);

        // ── ④ ワンタイム受諾URL
        $acceptUrl = URL::temporarySignedRoute(
            'invites.accept',
            $invitation->expires_at,
            ['token' => $invitation->token]
        );

        // ── ⑤ 招待メール送信（ここを修正）
        Notification::route('mail', $invitation->email)
            ->notify(new CompanyInvitationNotification($acceptUrl, $expiresDays));

        return response()->noContent();
    }

    // resend()/cancel() は前のままでOK
}
