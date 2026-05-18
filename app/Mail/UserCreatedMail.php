<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function build()
    {
        return $this
<<<<<<< HEAD
            ->from('SV001064@nossaseguros.ao', 'Noss')
=======
            ->from('SV001064@nossaseguros.ao', 'Keepcomply')
>>>>>>> 533c1e98e7ac34a654124189b8b2ce30645150f5
            ->subject('Seu código de autenticação')
            ->view('emails.user_created');
    }
    
    
}
