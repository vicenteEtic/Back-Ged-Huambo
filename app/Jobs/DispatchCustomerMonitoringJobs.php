<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchCustomerMonitoringJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

public int $tries = 5;
    public int $timeout = 60;

    public function handle(): void
    {
        Entities::select('id')
            ->chunkById(500, function ($entities) {
                foreach ($entities as $entity) {
                    MonitorSingleCustomerActivity::dispatch($entity->id);
                }
            });
    }
}
