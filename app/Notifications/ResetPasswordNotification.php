<?php

namespace App\Notifications;

use App\Support\FrontUrl;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    public function toMail($notifiable)
    {
        $resetUrl = FrontUrl::resetPasswordUrl(
            $this->token,
            $notifiable->getEmailForPasswordReset()
        );

        return (new MailMessage)
            ->subject('Redefinição de Senha')
            ->markdown('emails.reset-password', [
                'resetUrl' => $resetUrl,
                'notifiable' => $notifiable,
            ]);
    }
}
