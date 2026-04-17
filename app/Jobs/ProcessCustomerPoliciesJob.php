<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Services\KYT\CustomerKYTService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCustomerPoliciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;

    private int $customerId;
    private array $policyNumbers;

    public function __construct(int $customerId, array $policyNumbers)
    {
        $this->customerId = $customerId;
        $this->policyNumbers = $policyNumbers;
    }

    public function handle(CustomerKYTService $kytService)
    {
        Log::info("🚀 Processando policies", [
            'customer_id' => $this->customerId,
            'count' => count($this->policyNumbers)
        ]);

        $customer = Entities::find($this->customerId);
        if (!$customer) return;

        /**
         * 🔥 POLICIES
         */
        $policies = DB::table('policies_staging')
            ->whereIn('numero_apolice', $this->policyNumbers)
            ->get()
            ->toArray();

        /**
         * 🔥 RECEIPTS
         */
        $receipts = DB::table('recibos_cobrados')
            ->whereIn('numero_apolice', $this->policyNumbers)
            ->get()
            ->toArray();

        /**
         * 🔥 CHANGES
         */
        $changes = DB::table('policy_changes_staging')
            ->whereIn('numero_apolice', $this->policyNumbers)
            ->get()
            ->toArray();

        /**
         * 🔥 REFUNDS
         */
        $refunds = DB::table('apol_anulada_estorno')
            ->whereIn('n_apolice', $this->policyNumbers)
            ->get()
            ->toArray();

        /**
         * 🔥 BENEFICIÁRIOS
         */
        $beneficiaries = DB::table('beneficiarios_staging')
            ->whereIn('numero_apolice', $this->policyNumbers)
            ->get()
            ->toArray();

        /**
         * 🚀 Executa KYT
         */
        $kytService->runAllChecksMemory(
            $customer,
            $policies,
            $changes,
            $refunds,
            $receipts,
            $beneficiaries
        );

        unset($policies, $changes, $refunds, $receipts, $beneficiaries);

        gc_collect_cycles();
    }
}