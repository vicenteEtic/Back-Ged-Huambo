<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Services\KYT\CustomerKYTService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCustomerPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    private int $customerId;
    private array $policies;
    private array $changes;
    private array $refunds;
    private array $receipts;

    public function __construct(
        int $customerId,
        array $policies,
        array $changes,
        array $refunds = [],
        array $receipts = []
    ) {
        $this->customerId = $customerId;
        $this->policies = $policies;
        $this->changes = $changes;
        $this->refunds = $refunds;
        $this->receipts = $receipts;
    }

    public function handle(CustomerKYTService $kytService)
    {
        $customer = Entities::find($this->customerId);
        if (!$customer) return;

        $kytService->runAllChecksMemory(
            $customer,
            $this->policies,
            $this->changes,
            $this->refunds,
            $this->receipts
        );
    }
}