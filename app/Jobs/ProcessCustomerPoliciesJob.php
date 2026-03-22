<?php
namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Services\KYT\CustomerKYTService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCustomerPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private Entities $customer;
    private array $policies;

    public function __construct(Entities $customer, array $policies)
    {
        $this->customer = $customer;
        $this->policies = $policies;
    }

    public function handle(CustomerKYTService $kyt): void
    {
        try {
            Log::info("▶️ Processando cliente {$this->customer->customer_number} com " . count($this->policies) . " apólices");
            $kyt->runAllChecksMemory($this->customer, $this->policies);
        } catch (\Exception $e) {
            Log::error("❌ Erro KYT {$this->customer->customer_number}: " . $e->getMessage());
        }
    }
}