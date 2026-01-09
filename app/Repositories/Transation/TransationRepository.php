<?php

namespace App\Repositories\Transation;

use App\Models\Transation\Transation;
use App\Repositories\AbstractRepository;
use App\Repositories\AmlAlert\AmlAlertRepository;
use Illuminate\Support\Facades\Log;

class TransationRepository extends AbstractRepository
{
    protected $amlAlertRepository;

    public function __construct(Transation $model, AmlAlertRepository $amlAlertRepository)
    {
        $this->amlAlertRepository = $amlAlertRepository;
        parent::__construct($model);
    }

  public function storeManyTransactions(array $transactions)
{
    $now = now();
    $saved = [];

    foreach ($transactions as $tx) {
        $saved[] = $this->model::create([
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

    return [
        'transactions' => $saved,
        'ml_evaluation' => $this->evaluateAllClients()
    ];
}


public function evaluateAllClients(): array
{
    $allAlerts = [];
    $totalAlerts = 0;

    $clientsTransactions = Transation::all()->groupBy('client_id');

    foreach ($clientsTransactions as $clientId => $transactions) {
        $txArray = $transactions->map(function ($tx) {
            return [
                'transaction_uid'   => $tx->transaction_uid,
                'user_id'           => $tx->client_id,
                'amount'            => (float) $tx->amount,
                'currency'          => $tx->currency ?? 'AOA',
                'transaction_type'  => $tx->transaction_type,
                'transaction_date'  => $tx->transaction_date instanceof \Carbon\Carbon
                    ? $tx->transaction_date->toDateTimeString()
                    : (string) $tx->transaction_date,
                'payment_channel'   => $tx->payment_channel ?? 'local',
                'status'            => $tx->status ?? 'pending',
            ];
        })->toArray();

        $result = $this->amlAlertRepository->evaluateTransactions($txArray, $clientId);

        $allAlerts = array_merge($allAlerts, $result['alerts']);
        $totalAlerts += $result['total_alerts'];
    }

    return [
        'alerts' => $allAlerts,
        'total_alerts' => $totalAlerts
    ];
}


}
