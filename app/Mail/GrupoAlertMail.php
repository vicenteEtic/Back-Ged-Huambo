<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Alert\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GrupoAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $alert;
    public $host;

    public function __construct(User $user, Alert $alert, string $host)
    {
        $this->user  = $user;
        $this->alert = $alert;
        $this->host  = $host;
    }

    public function build()
    {
        return $this->subject('Novo alerta AML')
        ->view('emails.grupo.alert')->with([
            'user'  => $this->user,
            'alert' => $this->alert,
            'host'  => $this->host,
        ]);;
    }
}
