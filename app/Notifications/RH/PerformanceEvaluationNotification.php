<?php

namespace App\Notifications\RH;

use App\Models\RH\Performance\PerformanceEvaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PerformanceEvaluationNotification extends Notification
{
    use Queueable;

    public PerformanceEvaluation $evaluation;
    public string $eventType; // pending, completed, feedback

    public function __construct(PerformanceEvaluation $evaluation, string $eventType)
    {
        $this->evaluation = $evaluation;
        $this->eventType = $eventType;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = match ($this->eventType) {
            'pending' => 'Avaliação de Desempenho Pendente',
            'completed' => 'Avaliação de Desempenho Concluída',
            'feedback' => 'Feedback de Avaliação Disponível',
            default => 'Notificação de Avaliação',
        };

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.rh.performance_evaluation', [
                'evaluation' => $this->evaluation,
                'eventType' => $this->eventType,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'evaluation_id' => $this->evaluation->id,
            'employee_name' => $this->evaluation->employee->full_name,
            'cycle_name' => $this->evaluation->cycle?->name,
            'event_type' => $this->eventType,
            'message' => match ($this->eventType) {
                'pending' => 'Avaliação de desempenho pendente para ' . $this->evaluation->employee->full_name,
                'completed' => 'Avaliação de ' . $this->evaluation->employee->full_name . ' foi concluída.',
                'feedback' => 'Feedback disponível para a avaliação de ' . $this->evaluation->employee->full_name,
                default => 'Notificação de avaliação de desempenho',
            },
            'type' => 'performance_' . $this->eventType,
        ];
    }
}
