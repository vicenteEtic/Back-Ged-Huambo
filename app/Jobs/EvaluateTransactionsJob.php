<?php

namespace App\Jobs;

use App\Repositories\AmlAlert\AmlAlertRepository;
use App\Models\Transation\Transation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateTransactionsJob implements ShouldQueue
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
        $allAlerts = [];
        $totalAlerts = 0;

        $clientsTransactions = collect($this->transactions)->groupBy('client_id');

        foreach ($clientsTransactions as $clientId => $transactions) {
            $txArray = $transactions->map(function ($tx) {
                return [
                    'transaction_uid'   => $tx['transaction_uid'],
                    'user_id'           => $tx['client_id'],
                    'amount'            => (float) $tx['amount'],
                    'currency'          => $tx['currency'] ?? 'AOA',
                    'transaction_type'  => $tx['transaction_type'],
                    'transaction_date'  => $tx['transaction_date'] instanceof \Carbon\Carbon
                        ? $tx['transaction_date']->toDateTimeString()
                        : (string) $tx['transaction_date'],
                    'payment_channel'   => $tx['payment_channel'] ?? 'local',
                    'status'            => $tx['status'] ?? 'pending',
                ];
            })->toArray();

            $result = $this->amlAlertRepository->evaluateTransactions($txArray, $clientId);

            $allAlerts = array_merge($allAlerts, $result['alerts']);
            $totalAlerts += $result['total_alerts'];
        }

         $this->amlAlertRepository->storeAlerts($allAlerts);
    }
}
