<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Services\KYT\CustomerKYTService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class MonitorSingleCustomerActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 300;
    public array $backoff = [60, 120, 300];

    public function __construct(public int $entityId) {}

    public function handle(CustomerKYTService $kyt): void
    {
        try {
            $customer = Entities::find($this->entityId);

            if (!$customer) {
                return;
            }

            $kyt->runAllChecks($customer);

        } catch (Throwable $e) {
            Log::error('❌ Erro KYT Cliente', [
                'entity_id' => $this->entityId,
                'error' => $e->getMessage(),
            ]);

            throw $e; // permite retry controlado
        }
    }
}
