<?php

namespace App\Console\Commands\RH;

use App\Models\RH\Performance\PerformanceEvaluation;
use App\Notifications\RH\PerformanceEvaluationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckPendingEvaluationsCommand extends Command
{
    protected $signature = 'rh:check-pending-evaluations';
    protected $description = 'Notifica avaliadores sobre avaliações de desempenho pendentes';

    public function handle(): void
    {
        $pending = PerformanceEvaluation::where('status', 'pending')
            ->with(['employee.user', 'cycle'])
            ->get();

        $count = 0;
        foreach ($pending as $evaluation) {
            if ($evaluation->employee?->user) {
                Notification::send($evaluation->employee->user, new PerformanceEvaluationNotification($evaluation, 'pending'));
                $count++;
            }
        }

        $this->info("Notificações enviadas: {$count} avaliações pendentes.");
    }
}
