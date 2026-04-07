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

class ProcessCustomerPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    private int $customerId;
    private array $policies;
    private array $changes;

    public function __construct(int $customerId, array $policies, array $changes)
    {
        $this->customerId = $customerId;
        $this->policies = $policies;
        $this->changes = $changes;
    }

    public function handle(CustomerKYTService $kytService)
    {
        Log::info("🚀 KYT cliente {$this->customerId}");

        $customer = Entities::find($this->customerId);
        if (!$customer) return;

        $kytService->runAllChecksMemory(
            $customer,
            $this->policies,
            $this->changes
        );

        Log::info("✅ KYT concluído {$this->customerId}");
    }
}