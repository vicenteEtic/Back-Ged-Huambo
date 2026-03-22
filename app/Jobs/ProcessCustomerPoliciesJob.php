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

    public function __construct(int $customerId, array $policies)
    {
        $this->customerId = $customerId;
        $this->policies = $policies;
    }

    public function handle(CustomerKYTService $kytService)
    {
        Log::info("🚀 Iniciando KYT para cliente {$this->customerId}", ['policies_count' => count($this->policies)]);

        $customer = Entities::find($this->customerId);
        if (!$customer) {
            Log::warning("Cliente não encontrado: {$this->customerId}");
            return;
        }

        // Força todas as regras KYT, mesmo que dados sejam pequenos
        $kytService->runAllChecksMemory($customer, $this->policies);

        Log::info("✅ KYT concluído para cliente {$this->customerId}");
    }
}