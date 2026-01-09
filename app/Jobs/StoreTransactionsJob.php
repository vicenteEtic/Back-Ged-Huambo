<?php

namespace App\Jobs;

use App\Models\Transation\Transation;
use App\Repositories\AmlAlert\AmlAlertRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactions;
    protected $amlAlertRepository;

    public function __construct(array $transactions, AmlAlertRepository $amlAlertRepository)
    {
        $this->transactions = $transactions;
        $this->amlAlertRepository = $amlAlertRepository;
    }

    public function handle()
    {
        $now = now();
        $saved = [];

        foreach ($this->transactions as $tx) {
            $saved[] = Transation::create([
                'transaction_uid'  => $tx['transaction_uid'] ?? null,
                'transaction_date' => $tx['transaction_date'] ?? $now,
                'transaction_type' => $tx['transaction_type'] ?? 'transfer',
                'amount'           => $tx['amount'] ?? 0,
                'currency'         => $tx['currency'] ?? 'AOA',
                'payment_channel'  => $tx['payment_channel'] ?? 'local',
                'client_id'        => $tx['client_id'] ?? 'unknown',
                'policy_number'    => $tx['policy_number'] ?? null,
                'product_code'     => $tx['product_code'] ?? null,
                'beneficiary_id'   => $tx['beneficiary_id'] ?? null,
                'status'           => $tx['status'] ?? 'pending',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }

        // Dispatch job de avaliação AML
        EvaluateTransactionsJob::dispatch($saved, $this->amlAlertRepository);
    }
}
