<?php

namespace App\Notifications\RH;

use App\Models\RH\EmployeeDocument\EmployeeDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiryNotification extends Notification
{
    use Queueable;

    public EmployeeDocument $document;
    public int $daysLeft;

    public function __construct(EmployeeDocument $document, int $daysLeft)
    {
        $this->document = $document;
        $this->daysLeft = $daysLeft;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Documento a Vencer - ' . $this->document->name)
            ->markdown('emails.rh.document_expiry', [
                'document' => $this->document,
                'daysLeft' => $this->daysLeft,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'employee_name' => $this->document->employee->full_name ?? 'N/A',
            'expiry_date' => $this->document->expiry_date->format('Y-m-d'),
            'days_left' => $this->daysLeft,
            'type' => 'document_expiry',
        ];
    }
}
