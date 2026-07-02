<?php

namespace App\Notifications\RH;

use App\Models\RH\Leave\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestRejectedNotification extends Notification
{
    use Queueable;

    public LeaveRequest $leaveRequest;
    public string $reason;

    public function __construct(LeaveRequest $leaveRequest, string $reason)
    {
        $this->leaveRequest = $leaveRequest;
        $this->reason = $reason;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pedido de Férias Rejeitado')
            ->markdown('emails.rh.leave_rejected', [
                'leaveRequest' => $this->leaveRequest,
                'reason' => $this->reason,
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
            'reason' => $this->reason,
            'message' => 'Pedido de ' . ($this->leaveRequest->leaveType?->name ?? 'férias') . ' rejeitado: ' . $this->reason,
            'type' => 'leave_rejected',
        ];
    }
}
