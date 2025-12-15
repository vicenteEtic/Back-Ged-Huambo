<?php

namespace App\Repositories\Transation;

use App\Jobs\EvaluateTransationJob;
use App\Models\Transation\Transation;
use App\Repositories\AbstractRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TransationRepository extends AbstractRepository
{
    public function __construct(Transation $model)
    {
        parent::__construct($model);
    }

    public function storeManyTransactions(array $transactions)
    {
        $now = now();

        if (empty($transactions)) {
            return [
                'transactions' => [],
                'ml_evaluation' => ['alerts' => [], 'total_alerts' => 0]
            ];
        }

        $savedTransactions = [];

        // 1️⃣ Salva as transações
        foreach ($transactions as $tx) {
            // Valida campos obrigatórios mínimos
            if (!isset($tx['entite_id']) || !isset($tx['amount'])) {
                continue;
            }

            $data = [
                'entite_id'   => $tx['entite_id'],
                'amount'      => $tx['amount'],
                'currency'    => $tx['currency'] ?? 'AOA',
                'type'        => $tx['type'] ?? 'transfer',
                'status'      => $tx['status'] ?? 'pending',
                'channel'     => $tx['channel'] ?? 'local',
                'description' => $tx['description'] ?? null,
                'category'    => $tx['category'] ?? null,
                'ip_address'  => $tx['ip_address'] ?? null,
                'device'      => $tx['device'] ?? null,
                'notes'       => $tx['notes'] ?? null,
                'date'        => $tx['date'] ?? $now,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            $transaction = \App\Models\Transation\Transation::create($data);
            $savedTransactions[] = $transaction;
        }

        // 2️⃣ Envia transações ao ML Service de forma síncrona
        $mlResult = [
            'alerts' => [],
            'total_alerts' => 0
        ];

        if (!empty($savedTransactions)) {
            try {
                $responsePayton = Http::post('http://python-ml:5000/evaluate', [
                    'user_id' => $savedTransactions[0]->entite_id,
                    'transactions' => $savedTransactions
                ]);

                $mlResult = $responsePayton->json();

                // Opcional: loga alertas
                if (!empty($mlResult['alerts'])) {
                    foreach ($mlResult['alerts'] as $alert) {
                        Log::warning('Alerta AML gerado', [
                            'user_id' => $alert['user_id'],
                            'risk_score' => $alert['risk_score'],
                            'transaction' => $alert['transaction'],
                            'type' => 'AML_ALERT',
                            'status' => 'pending'
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erro ao chamar ML Service: ' . $e->getMessage());
            }
        }

        // 3️⃣ Retorna transações + avaliação ML
        return [
            'transactions' => $savedTransactions,
            'ml_evaluation' => $mlResult
        ];
    }
}
