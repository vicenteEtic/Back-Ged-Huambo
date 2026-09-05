<?php

namespace App\Console\Commands\RH;

use App\Models\RH\Leave\LeavePlan;
use App\Models\User;
use App\Notifications\RH\UpcomingLeaveNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckUpcomingLeavesCommand extends Command
{
    protected $signature = 'rh:check-upcoming-leaves';

    protected $description = 'Notifica responsáveis e RH sobre funcionários que entrarão de férias no mês seguinte';

    public function handle(): void
    {
        $nextMonth = now()->startOfMonth()->addMonth();

        $plans = LeavePlan::where('year', $nextMonth->year)
            ->where('expected_month', $nextMonth->month)
            ->whereNull('upcoming_notified_at')
            ->with(['employee.department', 'leaveType'])
            ->get();

        if ($plans->isEmpty()) {
            $this->info('Nenhum plano de férias para o mês seguinte.');

            return;
        }

        $monthLabel = LeavePlan::MONTHS[$nextMonth->month];

        $this->notifyDepartmentResponsibles($plans, $monthLabel, $nextMonth->year);
        $this->notifyRhUsers($plans, $monthLabel, $nextMonth->year);

        LeavePlan::whereIn('id', $plans->pluck('id'))
            ->update(['upcoming_notified_at' => now()]);

        $this->info("Notificações de férias enviadas para {$plans->count()} plano(s) do mês de {$monthLabel}.");
    }

    private function notifyDepartmentResponsibles($plans, string $monthLabel, int $year): void
    {
        foreach ($plans->groupBy(fn ($p) => $p->employee?->department_id) as $departmentId => $departmentPlans) {
            $responsible = $departmentPlans->first()->employee?->department?->responsible?->user;

            if (! $responsible) {
                continue;
            }

            $responsible->notify(new UpcomingLeaveNotification(
                $this->employeePayload($departmentPlans),
                $monthLabel,
                $year
            ));

            $this->line(" - Notificado {$responsible->first_name} (responsável do departamento)");
        }
    }

    private function notifyRhUsers($plans, string $monthLabel, int $year): void
    {
        $rhUsers = User::where('is_active', true)
            ->whereHas('role.permissions', fn ($q) => $q->where('permission.name', 'rh-ferias-show'))
            ->get();

        if ($rhUsers->isEmpty()) {
            return;
        }

        Notification::send($rhUsers, new UpcomingLeaveNotification(
            $this->employeePayload($plans),
            $monthLabel,
            $year
        ));

        $this->line(" - Notificados {$rhUsers->count()} utilizador(es) com permissão de RH Férias");
    }

    private function employeePayload($plans): array
    {
        return $plans->map(fn ($plan) => [
            'name' => $plan->employee?->full_name,
            'department' => $plan->employee?->department?->name,
            'days_entitled' => $plan->total_days_entitled,
            'leave_type' => $plan->leaveType?->name,
        ])->values()->all();
    }
}
