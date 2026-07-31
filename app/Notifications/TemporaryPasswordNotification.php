<?php

namespace App\Notifications;

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
        // Alteramos para usar o método 'view' e passar as variáveis pro template
        return (new MailMessage)
            ->subject('Sua Senha Temporária - Kixicrédito')
            ->view('emails.temp_password', [
                'user' => $notifiable, // Injeta a model User do banco
                'password' => $this->tempPassword, // Injeta a senha gerada
                'url' => url('/login') // Ajuste para a rota do seu front-end (ex: env('FRONTEND_URL'))
            ]);
    }
}
