<?php

namespace App\Services\Aml;

use App\Models\Transation\Transation;
use App\Models\AmlAlert\AmlAlert;
use App\Models\Indicator\IndicatorType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdvancedAmlEngine
{
    private array $productLimits = [];
    private array $highRiskCountries = [];

    public function __construct()
    {
        // Limites monetários por produto (dinâmico)
        $this->productLimits = IndicatorType::pluck('score', 'description')->toArray();

        // Países de risco elevado (dinâmico ou hardcoded)
        $this->highRiskCountries = ['IR', 'KP', 'SY', 'AF']; // Exemplo, pode vir de DB
    }

    /**
     * Avalia múltiplas transações
     */
    public function evaluate(array $transactions)
    {
        foreach ($transactions as $tx) {
            $this->checkProductLimit($tx);
            $this->checkFrequency($tx);
            $this->checkProfileDeviation($tx);
            $this->checkFractionedPayments($tx);
            $this->checkBeneficiaryChange($tx);
            $this->checkHighRiskCountry($tx);
            $this->checkMlRisk($tx); // opcional, integração com Flask ML
        }
    }

    // 1️⃣ Limites por produto
    private function checkProductLimit(array $tx)
    {
        $product = $tx['product_code'] ?? null;
        $amount = $tx['amount'] ?? 0;

        $limit = $this->productLimits[$product] ?? 1000000; // default se não existir no DB
        if ($amount > $limit) {
            $this->createAlert($tx, "Transação acima do limite do produto {$product}", 'high', 10);
        }
    }

    // 2️⃣ Frequência de transações (todos os dias, não só hoje)
    private function checkFrequency(array $tx)
    {
        $clientId = $tx['client_id'] ?? null;
        if (!$clientId) return;

        $recentTxs = Transation::where('client_id', $clientId)
            ->where('transaction_date', '>=', now()->subDays(7))
            ->get();

        $dailyCounts = [];
        foreach ($recentTxs as $t) {
            $day = $t->transaction_date->format('Y-m-d');
            $dailyCounts[$day] = ($dailyCounts[$day] ?? 0) + 1;
        }

        foreach ($dailyCounts as $day => $count) {
            if ($count > 5) {
                $this->createAlert($tx, "Muitas transações no dia {$day}", 'medium', 7);
            }
        }
    }

    // 3️⃣ Desvio do perfil histórico do cliente
    private function checkProfileDeviation(array $tx)
    {
        $clientId = $tx['client_id'] ?? null;
        $product = $tx['product_code'] ?? null;
        $amount = $tx['amount'] ?? 0;

        if (!$clientId || !$product || !$amount) return;

        $historical = Transation::where('client_id', $clientId)
            ->where('product_code', $product)
            ->where('transaction_date', '>=', now()->subMonths(3))
            ->pluck('amount');

        if ($historical->isEmpty()) return;

        $avg = $historical->avg();
        $std = $historical->stddev() ?: 0;
        $threshold = max($avg * 3, $avg + 2 * $std);

        if ($amount > $threshold) {
            $this->createAlert($tx, "Transação fora do perfil histórico do cliente", 'high', 9);
        }
    }

    // 4️⃣ Pagamentos ou recebimentos fraccionados
    private function checkFractionedPayments(array $tx)
    {
        $clientId = $tx['client_id'] ?? null;
        $amount = $tx['amount'] ?? 0;
        $date = $tx['transaction_date'] ?? now();

        if (!$clientId) return;

        $sumRecent = Transation::where('client_id', $clientId)
            ->where('transaction_date', '>=', now()->subDays(1))
            ->sum('amount');

        if ($sumRecent > 1000000 && $sumRecent < 2000000) {
            $this->createAlert($tx, "Transações fraccionadas detectadas", 'medium', 6);
        }
    }

    // 5️⃣ Mudança de beneficiário ou conta bancária
    private function checkBeneficiaryChange(array $tx)
    {
        $clientId = $tx['client_id'] ?? null;
        $beneficiary = $tx['beneficiary_id'] ?? null;

        if (!$clientId || !$beneficiary) return;

        $lastBeneficiary = Transation::where('client_id', $clientId)
            ->orderBy('transaction_date', 'desc')
            ->value('beneficiary_id');

        if ($lastBeneficiary && $lastBeneficiary !== $beneficiary) {
            $this->createAlert($tx, "Alteração recente de beneficiário detectada", 'high', 8);
        }
    }

    // 6️⃣ País ou entidade de risco elevado
    private function checkHighRiskCountry(array $tx)
    {
        $country = $tx['country'] ?? null;
        if ($country && in_array($country, $this->highRiskCountries)) {
            $this->createAlert($tx, "Transação para país de risco elevado", 'high', 10);
        }
    }

    // 7️⃣ Integração com Flask ML
    private function checkMlRisk(array $tx)
    {
        try {
            $response = Http::post('http://python-ml:5000/evaluate', [
                'transactions' => [$tx]
            ]);

            $result = $response->json();
            if (!empty($result['alerts'])) {
                foreach ($result['alerts'] as $alert) {
                    $this->createAlert(
                        $tx,
                        $alert['reason'] ?? 'Alerta ML',
                        strtolower($alert['severity'] ?? 'medium'),
                        $alert['risk_score'] ?? 5
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error("Erro ao chamar Flask ML: " . $e->getMessage());
        }
    }

    // Criação do alerta
  private array $existingAlerts = [];

private function createAlert(array $tx, string $reason, string $severity, int $riskScore)
{
    $key = $tx['transaction_uid'] ?? $tx['client_id'] . '-' . $reason;

    if (isset($this->existingAlerts[$key])) return; // evita duplicados

    $this->existingAlerts[$key] = true;

    AmlAlert::create([
        'transaction_id'  => $tx['id'] ?? null,
        'transaction_ref' => $tx['transaction_uid'] ?? null,
        'client_id'       => $tx['client_id'] ?? 'unknown',
        'severity'        => $severity,
        'reason'          => $reason,
        'risk_score'      => $riskScore,
        'status'          => 'aberto'
    ]);
}


}
