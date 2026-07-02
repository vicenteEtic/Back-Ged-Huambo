<?php

namespace App\Notifications\RH;

use App\Models\RH\Career\ProgressionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProgressionRejectedNotification extends Notification
{
    use Queueable;

    public ProgressionRequest $progression;
    public string $reason;

    public function __construct(ProgressionRequest $progression, string $reason)
    {
        $this->progression = $progression;
        $this->reason = $reason;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Progressão Rejeitada')
            ->markdown('emails.rh.progression_rejected', [
                'progression' => $this->progression,
                'reason' => $this->reason,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'progression_request_id' => $this->progression->id,
            'employee_name' => $this->progression->employee->full_name,
            'type' => $this->progression->type,
            'reason' => $this->reason,
            'message' => 'Sua solicitação de ' . ($this->progression->type ?? 'progressão') . ' foi rejeitada: ' . $this->reason,
            'type_label' => 'progression_rejected',
        ];
    }
}
