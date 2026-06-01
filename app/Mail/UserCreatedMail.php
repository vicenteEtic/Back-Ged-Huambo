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
            ->subject('Criação de conta - Keepcomply')
            ->view('emails.user_created')
            ->with([
                'user' => $this->user,
                'password' => $this->password,
            ]);
    }
}
