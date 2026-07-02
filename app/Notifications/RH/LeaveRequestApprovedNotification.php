<?php

namespace App\Notifications\RH;

use App\Models\RH\Leave\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestApprovedNotification extends Notification
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
            ->subject('Pedido de Férias Aprovado')
            ->markdown('emails.rh.leave_approved', [
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
            'status' => 'approved',
            'message' => 'Pedido de ' . ($this->leaveRequest->leaveType?->name ?? 'férias') . ' aprovado.',
            'type' => 'leave_approved',
        ];
    }
}
