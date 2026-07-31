<?php

namespace App\Notifications;

use App\Support\FrontUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemporaryPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $tempPassword;

    public function __construct(string $tempPassword)
    {
        $this->tempPassword = $tempPassword;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sua Senha Temporária - '.config('app.name'))
            ->view('emails.temp_password', [
                'user' => $notifiable,
                'password' => $this->tempPassword,
                'url' => FrontUrl::frontend().'/login',
            ]);
    }
}
