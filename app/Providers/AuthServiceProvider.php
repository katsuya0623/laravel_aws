<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

// ▼ 追加
use App\Models\WorkHistory;
use App\Policies\WorkHistoryPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // ▼ 追加：WorkHistory の閲覧専用ポリシー
        WorkHistory::class => WorkHistoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // ✅ メール認証文面を日本語化
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('【nibi】メールアドレス確認のお願い')
                ->greeting('こんにちは！')
                ->line('メールアドレスを確認するには、下のボタンをクリックしてください。')
                ->action('メールアドレスを確認する', $url)
                ->line('アカウントを作成していない場合は、このメールは無視してください。')
                ->salutation('今後ともよろしくお願いいたします。nibi');
        });
    }
}
