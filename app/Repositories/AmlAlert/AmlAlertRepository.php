<?php

namespace App\Repositories\AmlAlert;

use App\Models\AmlAlert\AmlAlert;
use App\Models\Transation\Transation;
use App\Repositories\AbstractRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmlAlertRepository extends AbstractRepository
{
    public function __construct(AmlAlert $model)
    {
        parent::__construct($model);
    }

    public function evaluateTransactions(array $transactions, $clientId)
    {
        // Se não houver transações, retorna vazio
        if (empty($transactions)) {
            return ['alerts' => [], 'total_alerts' => 0];
        }

        try {
            $response = Http::post('http://python-ml:5000/evaluate', [
                'transactions' => $transactions
            ]);

            $result = $response->json();
            if (!is_array($result)) {
                Log::error('Resposta do Flask ML não é array', ['response' => $response->body()]);
                $result = [];
            }
        } catch (\Exception $e) {
            Log::error("Erro ao chamar Flask ML: " . $e->getMessage());
            return ['alerts' => [], 'total_alerts' => 0];
        }

        // Garante que 'alerts' seja sempre array
        $alerts = is_array($result['alerts'] ?? null) ? $result['alerts'] : [];

        Log::info("Alertas recebidos do Flask ML", ['alerts' => $alerts]);

        foreach ($alerts as $alert) {
            if (!is_array($alert)) continue;

            $transactionRef = $alert['transaction_ref'] ?? $alert['transaction_uid'] ?? null;

            // Placeholder se não houver referência
            if (!$transactionRef) {
                $transactionRef = 'AGGREGATE-' . ($alert['user_id'] ?? 'unknown') . '-' . date('YmdHis');
            }

            $transactionId = Transation::where('transaction_uid', $alert['transaction_uid'] ?? $transactionRef)->value('id');

            // Evita duplicidade
            $exists = AmlAlert::where('client_id', $alert['user_id'] ?? 'unknown')
                ->where('transaction_ref', $transactionRef)
                ->exists();
            if ($exists) continue;

            AmlAlert::create([
                'transaction_id'  => $transactionId,
                'transaction_ref' => $transactionRef,
                'client_id'       => $alert['user_id'] ?? 'unknown',
                'severity'        => strtolower($alert['severity'] ?? 'medium'),
                'reason'          => $alert['reason'] ?? 'Não especificado',
                'risk_score'      => $alert['risk_score'] ?? 0,
                'status'          => 'aberto'
            ]);
        }


        return [
            'alerts' => $alerts,
            'total_alerts' => count($alerts)
        ];
    }
}
