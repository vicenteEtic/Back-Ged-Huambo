<?php

namespace App\Notifications\RH;

use App\Models\RH\Career\ProgressionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProgressionSubmittedNotification extends Notification
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
            ->subject('Nova Solicitação de Progressão - ' . $this->progression->employee->full_name)
            ->markdown('emails.rh.progression_submitted', [
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
            'from_category' => $this->progression->from_category,
            'to_category' => $this->progression->to_category,
            'message' => 'Nova solicitação de ' . ($this->progression->type ?? 'progressão') . ' de ' . $this->progression->employee->full_name,
            'type_label' => 'progression_submitted',
        ];
    }
}
