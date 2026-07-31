<?php

namespace App\Notifications\RH;

use App\Models\RH\Payroll\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayslipGeneratedNotification extends Notification
{
    use Queueable;

    public Payslip $payslip;

    public function __construct(Payslip $payslip)
    {
        $this->payslip = $payslip->load('period', 'employee');
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Título de Vencimento - ' . ($this->payslip->period?->name ?? 'Recibo de Salário'))
            ->markdown('emails.rh.payslip', [
                'payslip' => $this->payslip,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'payslip_id' => $this->payslip->id,
            'payslip_number' => $this->payslip->payslip_number,
            'period' => $this->payslip->period?->name,
            'employee_name' => $this->payslip->employee->full_name ?? 'N/A',
            'net_pay' => $this->payslip->net_pay,
            'type' => 'payslip',
        ];
    }
}
