<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailJa extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('【nibi】メールアドレスをご確認ください')
            ->markdown('mail.verify-ja', ['url' => $url])
            // ★ここがポイント：送信時にロゴをメール本文へ埋め込む
            ->withSymfonyMessage(function ($message) {
                $message->embedFromPath(public_path('images/logo.png'), 'nibi-logo', 'image/png');
            });
    }
}
