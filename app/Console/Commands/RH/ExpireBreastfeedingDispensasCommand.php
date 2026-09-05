<?php

namespace App\Console\Commands\RH;

use App\Services\RH\Attendance\AttendanceRequestService;
use Illuminate\Console\Command;

class ExpireBreastfeedingDispensasCommand extends Command
{
    protected $signature = 'rh:expire-breastfeeding-dispensas';

    protected $description = 'Expira dispensas de amamentação cujo benefício ultrapassou os 18 meses';

    public function handle(AttendanceRequestService $service): void
    {
        $result = $service->expireBreastfeedingBenefits();

        if ($result['expired'] === 0) {
            $this->info('Nenhum benefício de amamentação expirado.');

            return;
        }

        $this->info("Benefícios de amamentação expirados: {$result['expired']}.");
    }
}
