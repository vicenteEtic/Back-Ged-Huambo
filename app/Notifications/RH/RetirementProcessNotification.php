<?php

namespace App\Notifications\RH;

use App\Models\RH\Career\RetirementProcess;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RetirementProcessNotification extends Notification
{
    use Queueable;

    public RetirementProcess $process;

    public function __construct(RetirementProcess $process)
    {
        $this->process = $process;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Actualização do Processo de Reforma')
            ->markdown('emails.rh.retirement_update', [
                'process' => $this->process,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'retirement_process_id' => $this->process->id,
            'employee_name' => $this->process->employee->full_name,
            'status' => $this->process->status,
            'message' => 'Processo de reforma de ' . $this->process->employee->full_name . ' actualizado para: ' . $this->process->status,
            'type' => 'retirement_update',
        ];
    }
}
