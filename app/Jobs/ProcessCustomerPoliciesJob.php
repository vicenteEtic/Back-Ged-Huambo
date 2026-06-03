<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Services\KYT\KYTService;
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
    public $tries = 20;

    public int $customerId;
    public array $policyNumbers = [];
    public array $policies = [];
    public array $changes = [];
    public array $refunds = [];
    public array $receipts = [];
    public array $beneficiaries = [];

    public function __construct(
        int $customerId,
        array $policyNumbers = [],
        array $policies = [],
        array $changes = [],
        array $refunds = [],
        array $receipts = [],
        array $beneficiaries = []
    ) {
        $this->customerId = $customerId;
        $this->policyNumbers = $policyNumbers;
        $this->policies = $policies;
        $this->changes = $changes;
        $this->refunds = $refunds;
        $this->receipts = $receipts;
        $this->beneficiaries = $beneficiaries;
    }

    public function handle(KYTService $kytService)
    {
        if (empty($this->policyNumbers)) {
            Log::warning("⚠️ Job sem policies", [
                'customer_id' => $this->customerId
            ]);
            return;
        }

        $customer = Entities::find($this->customerId);
        if (!$customer) return;

        /**
         * 🔥 Usa dados pré-buscados (queries feitas no ProcessCustomerDataJob)
         */
        $policies = $this->policies ?: DB::table('policies_staging')
            ->whereIn('numero_apolice', $this->policyNumbers)->get()->toArray();

        $changes = $this->changes ?: DB::table('policy_changes_staging')
            ->whereIn('numero_apolice', $this->policyNumbers)->get()->toArray();

        $refunds = $this->refunds ?: DB::table('apol_anulada_estorno')
            ->whereIn('n_apolice', $this->policyNumbers)->get()->toArray();

        $receipts = $this->receipts ?: DB::table('recibos_cobrados')
            ->whereIn('numero_apolice', $this->policyNumbers)->get()->toArray();

        $beneficiaries = $this->beneficiaries ?: DB::table('beneficiarios_staging')
            ->whereIn('numero_apolice', $this->policyNumbers)->get()->toArray();

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