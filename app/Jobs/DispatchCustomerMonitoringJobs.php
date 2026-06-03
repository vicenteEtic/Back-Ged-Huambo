<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class DispatchCustomerMonitoringJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;

    public function handle(): void
    {
        $jobs = [];

        Entities::select('id')
            ->chunkById(500, function ($entities) use (&$jobs) {
                foreach ($entities as $entity) {
                    $jobs[] = new MonitorSingleCustomerActivity($entity->id);
                }
            });

        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->onQueue('cliente')
                ->allowFailures()
                ->finally(function (Batch $batch) {
                    Log::info("✅ Monitor batch concluído", [
                        'total' => $batch->totalJobs,
                    ]);
                })
                ->dispatch();
        }
    }
}
