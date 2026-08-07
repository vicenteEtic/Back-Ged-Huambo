<?php

namespace App\Console\Commands\RH;

use App\Services\RH\Leave\HolidayService;
use Illuminate\Console\Command;

class SyncHolidaysCommand extends Command
{
    protected $signature = 'rh:sync-holidays {--year=} {--country=AO}';

    protected $description = 'Sincroniza os feriados nacionais a partir de date.nager.at';

    public function handle(HolidayService $service): int
    {
        $year = $this->option('year') ? (int) $this->option('year') : now()->year;

        try {
            $count = $service->syncFromNager($year, (string) $this->option('country'));
            $this->info("{$count} feriados de {$year} sincronizados com sucesso.");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
