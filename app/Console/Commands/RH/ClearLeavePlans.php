<?php

namespace App\Console\Commands\RH;

use App\Models\RH\Leave\LeavePlan;
use Illuminate\Console\Command;

class ClearLeavePlans extends Command
{
    protected $signature = 'rh:clear-leave-plans';

    protected $description = 'Apaga todos os dados do plano de férias (leave_plans)';

    public function handle(): void
    {
        $count = LeavePlan::withTrashed()->count();
        LeavePlan::withTrashed()->forceDelete();

        $this->info("Planos de férias apagados: {$count}.");
    }
}
