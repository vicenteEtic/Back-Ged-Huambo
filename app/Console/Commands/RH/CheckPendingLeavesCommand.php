<?php

namespace App\Console\Commands\RH;

use App\Models\RH\Leave\LeaveRequest;
use Illuminate\Console\Command;

class CheckPendingLeavesCommand extends Command
{
    protected $signature = 'rh:check-pending-leaves';
    protected $description = 'Verifica pedidos de férias pendentes há mais de 3 dias';

    public function handle(): void
    {
        $threshold = now()->subDays(3);

        $stale = LeaveRequest::where('status', 'pending')
            ->where('created_at', '<', $threshold)
            ->with(['employee', 'employee.department.responsible'])
            ->get();

        if ($stale->isEmpty()) {
            $this->info('Nenhum pedido pendente há mais de 3 dias.');
            return;
        }

        $this->warn("{$stale->count()} pedido(s) pendente(s) há mais de 3 dias:");
        foreach ($stale as $leave) {
            $this->line(" - #{$leave->id}: {$leave->employee->full_name} ({$leave->start_date->format('d/m/Y')} a {$leave->end_date->format('d/m/Y')})");
        }
    }
}
