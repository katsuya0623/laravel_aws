<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CompanyInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $acceptUrl,
        public int $expiresDays = 7,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('【dousoko】企業アカウントのご招待')
            ->greeting('こんにちは！')
            ->line('管理者から、企業アカウントのご招待が届いています。')
            ->line("下のボタンからパスワードを設定し、アカウントを有効化してください。")
            ->action('パスワードを設定する', $this->acceptUrl)   // ★ ここが肝
            ->line("※ 招待リンクの有効期限は {$this->expiresDays} 日です。期限切れの場合は再送をご依頼ください。")
            ->salutation('dousoko サポート');
    }
}
