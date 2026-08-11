<?php

namespace App\Notifications\RH;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingLeaveNotification extends Notification
{
    use Queueable;

    public array $employees;

    public string $monthLabel;

    public int $year;

    public function __construct(array $employees, string $monthLabel, int $year)
    {
        $this->employees = $employees;
        $this->monthLabel = $monthLabel;
        $this->year = $year;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Férias do mês de '.$this->monthLabel.' — preparação antecipada')
            ->markdown('emails.rh.upcoming_leave', [
                'employees' => $this->employees,
                'monthLabel' => $this->monthLabel,
                'year' => $this->year,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'month' => $this->monthLabel,
            'year' => $this->year,
            'employees' => collect($this->employees)->map(fn ($e) => [
                'name' => $e['name'] ?? null,
                'department' => $e['department'] ?? null,
                'days_entitled' => $e['days_entitled'] ?? null,
            ])->values(),
            'message' => 'Funcionários que entrarão de férias em '.$this->monthLabel.' de '.$this->year.'. Preparem a guia de férias, o subsídio e demais procedimentos.',
            'type' => 'upcoming_leave',
        ];
    }
}
