<?php

namespace App\Notifications\RH;

use App\Models\RH\Leave\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmittedNotification extends Notification
{
    use Queueable;

    public LeaveRequest $leaveRequest;

    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Novo Pedido de Férias - ' . $this->leaveRequest->employee->full_name)
            ->markdown('emails.rh.leave_submitted', [
                'leaveRequest' => $this->leaveRequest,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'leave_request_id' => $this->leaveRequest->id,
            'employee_name' => $this->leaveRequest->employee->full_name,
            'leave_type' => $this->leaveRequest->leaveType?->name,
            'start_date' => $this->leaveRequest->start_date->format('Y-m-d'),
            'end_date' => $this->leaveRequest->end_date->format('Y-m-d'),
            'total_days' => $this->leaveRequest->total_days,
            'message' => 'Novo pedido de ' . ($this->leaveRequest->leaveType?->name ?? 'férias') . ' de ' . $this->leaveRequest->employee->full_name,
            'type' => 'leave_submitted',
        ];
    }
}
