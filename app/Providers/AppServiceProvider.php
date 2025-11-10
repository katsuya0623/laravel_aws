<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Support\RoleResolver;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // @role('enduser') / @role('company,admin') など
        Blade::if('role', function (string $csvRoles) {
            $user = auth('admin')->user() ?? auth('web')->user();
            if (!$user) return false;

            $current = (string) (RoleResolver::resolve($user) ?? '');
            $allowed = array_filter(array_map('trim', explode(',', $csvRoles)));
            return in_array($current, $allowed, true);
        });

        // 1) パスワード再設定URLを https://dousoko.com/reset-password/{token}?email=... に固定
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $base  = env('APP_FRONTEND_URL', config('app.url')); // .envのAPP_FRONTEND_URL優先
            $email = urlencode($notifiable->getEmailForPasswordReset());
            $url   = rtrim($base, '/') . "/reset-password/{$token}?email={$email}";
            Log::info('ResetPassword URL built', ['base' => $base, 'url' => $url]);
            return $url;
        });

        // 2) メール本文を日本語に上書き
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $base  = env('APP_FRONTEND_URL', config('app.url'));
            $email = urlencode($notifiable->getEmailForPasswordReset());
            $url   = rtrim($base, '/') . "/reset-password/{$token}?email={$email}";

            return (new MailMessage)
                ->subject('【dousoko】パスワード再設定のご案内')
                ->greeting('こんにちは。')
                ->line('アカウントのパスワード再設定のリクエストを受け付けました。')
                ->action('パスワードを再設定', $url)
                ->line('このリンクの有効期限は60分です。')
                ->line('お心当たりがない場合は、このメールは破棄してください。');
        });
    }
}
