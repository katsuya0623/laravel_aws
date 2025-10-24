<?php

namespace App\Http\Controllers\Invites;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password as PasswordRule;

class InviteAcceptController extends Controller
{
    /**
     * 招待受諾フォーム表示
     */
    public function show(Request $request, string $token)
    {
        $inv = CompanyInvitation::where('token', $token)->first();

        if (! $inv) {
            return redirect()->route('invites.expired');
        }

        // 期限切れ / 既に処理済みは無効
        if ($inv->status !== 'pending' || now()->greaterThan($inv->expires_at)) {
            return redirect()->route('invites.expired');
        }

        // 会社名も併せて表示できると親切
        $companyName = optional(Company::find($inv->company_id))->name ?? $inv->company_name ?? '';

        return view('invites.accept', [
            'token'        => $token,
            'email'        => $inv->email,
            'company_name' => $companyName,
        ]);
    }

    /**
     * 受諾完了（ユーザー作成/更新 → 会社と紐付け → 招待消化）
     */
    public function accept(Request $request, string $token)
    {
        $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'password'              => ['required', PasswordRule::defaults(), 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $inv = CompanyInvitation::where('token', $token)->lockForUpdate()->first();

        if (! $inv) {
            return redirect()->route('invites.expired')->with('error', '招待が見つかりません。');
        }
        if ($inv->status !== 'pending') {
            return redirect()->route('invites.expired')->with('error', 'この招待は使用済みです。');
        }
        if (now()->greaterThan($inv->expires_at)) {
            return redirect()->route('invites.expired')->with('error', '招待の有効期限が切れています。');
        }

        $user = null;

        DB::transaction(function () use ($request, $inv, &$user) {
            // 1) 招待メールと同じユーザーが既にいれば取得、いなければ作成
            $user = User::where('email', $inv->email)->first();

            if (! $user) {
                // 新規ユーザー作成（メールは招待のアドレスで固定）
                $user = User::create([
                    'name'              => $request->string('name')->toString(),
                    'email'             => $inv->email,
                    'password'          => Hash::make($request->string('password')->toString()),
                    'is_active'         => true, // プロジェクト要件に合わせて
                    'email_verified_at' => now(), // 招待経由は信頼済みとして即時検証扱い（要件に合わせて）
                ]);
            } else {
                // 既存ユーザーの場合はパスワード更新のみ（必要に応じて）
                $user->forceFill([
                    'name'     => $request->string('name')->toString() ?: $user->name,
                    'password' => Hash::make($request->string('password')->toString()),
                ])->save();

                // メール未検証なら検証済みにする（MustVerifyEmail 実装有無に合わせる）
                if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                    event(new Verified($user));
                } elseif (is_null($user->email_verified_at)) {
                    $user->email_verified_at = now();
                    $user->save();
                }
            }

            // 2) 会社とユーザーの紐付け（Pivot: company_user）
            //    既存レコードが無ければ作成
            $pivotExists = DB::table('company_user')
                ->where('company_id', $inv->company_id)
                ->where('user_id', $user->id)
                ->exists();

            if (! $pivotExists) {
                DB::table('company_user')->insert([
                    'company_id' => $inv->company_id,
                    'user_id'    => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 3) 招待を使用済みに更新
            $inv->status      = 'accepted';
            $inv->accepted_at = now();
            $inv->save();
        });

        // 4) ログインさせる
        Auth::login($user);

        // 5) リダイレクト先（存在チェックして賢く遷移）
        $redirectUrl = '/';
        if (app('router')->has('user.company.edit')) {
            // 企業プロフィール編集に誘導（要件に合わせて）
            $redirectUrl = route('user.company.edit');
        } elseif (app('router')->has('dashboard')) {
            $redirectUrl = route('dashboard');
        }

        return redirect($redirectUrl)->with('status', '招待を受諾し、アカウントを作成（または更新）しました。');
    }
}
