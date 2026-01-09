<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvaluateTransationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $transactions;

    public function __construct($userId, $transactions)
    {
        $this->userId = $userId;
        $this->transactions = $transactions;
    }

   public function handle()
{
    try {
        $response = Http::post("http://setupNossaSeguros-python-ml:5000/evaluate", [
            'user_id' => $this->userId,
            'transactions' => $this->transactions
        ]);

        $result = $response->json();

        if (!empty($result['alerts'])) {
            foreach ($result['alerts'] as $alert) {
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
        Log::error('Erro ao avaliar transações no ML Service: ' . $e->getMessage(), [
            'user_id' => $this->userId,
            'transactions_count' => count($this->transactions)
        ]);
    }
}

}
