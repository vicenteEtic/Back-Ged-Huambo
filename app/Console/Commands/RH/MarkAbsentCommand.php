<?php

namespace App\Console\Commands\RH;

use App\Services\RH\Attendance\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentCommand extends Command
{
    protected $signature = 'rh:mark-absent {--date=}';
    protected $description = 'Marca automaticamente como falta os funcionários sem registo de ponto no dia (default: ontem)';

    public function handle(AttendanceService $service): int
    {
        $date = $this->option('date') ?? Carbon::yesterday()->format('Y-m-d');

        $result = $service->markAbsentForDate($date);

        if ($result['skipped']) {
            $this->info("Data {$date} ignorada ({$result['skipped']}).");
            return self::SUCCESS;
        }

        $this->info("Faltas registadas para {$date}: {$result['marked']} funcionário(s).");

        return self::SUCCESS;
    }
}
