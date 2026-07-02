<?php

namespace App\Notifications\RH;

use App\Models\RH\Career\ProgressionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProgressionApprovedNotification extends Notification
{
    use Queueable;

    public ProgressionRequest $progression;

    public function __construct(ProgressionRequest $progression)
    {
        $this->progression = $progression;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Progressão Aprovada')
            ->markdown('emails.rh.progression_approved', [
                'progression' => $this->progression,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'progression_request_id' => $this->progression->id,
            'employee_name' => $this->progression->employee->full_name,
            'type' => $this->progression->type,
            'message' => 'Sua solicitação de ' . ($this->progression->type ?? 'progressão') . ' foi aprovada.',
            'type_label' => 'progression_approved',
        ];
    }
}
