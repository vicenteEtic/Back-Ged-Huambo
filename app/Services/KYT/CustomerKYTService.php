<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use App\Models\Entities\RiskAssessment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CustomerKYTService
{
    public $timeout = 100;
    public $tries = 8;
    public $backoff = 10;

    /* =========================
       RISK
    ========================== */

    public function RiskAssessmentEntity(Entities $customer): array
    {
        $cacheKey = "risk_assessment_entity_{$customer->id}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $risk = RiskAssessment::where('entity_id', $customer->id)->latest()->first();

        $data = [
            'risk_id' => $risk->id ?? null,
            'alert_priority' => $risk ? in_array($risk->diligence, ["Cliente Inaceitável", "Reforçada"]) : false,
            'valid' => (bool) $risk
        ];

        Cache::put($cacheKey, $data, now()->addHours(20));

        return $data;
    }

    /* =========================
       ENTRY POINT
    ========================== */

    public function runAllChecksMemory(
        Entities $customer,
        array $policies,
        array $changes = [],
        array $refunds = [],
        array $receipts = [],
        array $beneficiaries = []


    ): void {

        Log::info("KYT START", [
            'customer' => $customer->customer_number,
            'policies' => count($policies)
        ]);

        if (empty($policies)) return;

        $this->checkFrequentBeneficiaryChanges($customer, $beneficiaries);

        Log::info("KYT FINISHED", [
            'customer' => $customer->customer_number
        ]);
    }

    /* =========================
       SAFE DATE
    ========================== */

    private function safeDate($date): ?Carbon
    {
        try {
            if (!$date) return null;
            return Carbon::parse($date);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /* =========================
       KYT RULE
    ========================== */


   private function checkFrequentBeneficiaryChanges(
    Entities $customer,
    array $beneficiaries = []
): void {

    Log::info('KYT PRODUCT BENEFICIARY ANALYSIS START', [
        'customer' => $customer->customer_number,
        'records' => count($beneficiaries)
    ]);

    if (empty($beneficiaries)) return;

    $grouped = collect($beneficiaries)
        ->map(fn($b) => (array) $b)
        ->filter(fn($b) =>
            !empty($b['descricao_produto']) &&
            !empty($b['numero_apolice'])
        )
        ->groupBy('descricao_produto');

    foreach ($grouped as $produto => $records) {

        $records = $records->sortBy(fn($r) =>
            $this->safeDate($r['data_atualizacao_beneficiario'] ?? null)?->timestamp ?? 0
        )->values();

        if ($records->count() < 2) continue;

        $changes = 0;
        $history = [];
        $prev = null;

        foreach ($records as $r) {

            $beneficiaryId = strtoupper(trim((string) (
                $r['codigo_beneficiario']
                ?? $r['nome_beneficiario']
                ?? ''
            )));

            $type = strtoupper(trim((string) ($r['tipo_beneficiario'] ?? '')));

            if ($beneficiaryId === '') continue;

            $current = md5($beneficiaryId . '|' . $type);

            $history[] = $current;

            if ($prev !== null && $prev !== $current) {
                $changes++;
            }

            $prev = $current;
        }

        $unique = count(array_unique($history));

        // KYT RULE BASE
        if ($unique < 3 || $changes < 2) continue;

        $dates = $records->map(fn($r) =>
            $this->safeDate($r['data_atualizacao_beneficiario'] ?? null)
        )->filter();

        if ($dates->isEmpty()) continue;

        $min = $dates->min();
        $max = $dates->max();
        $days = $min->diffInDays($max);

        if ($days > 365) continue;

        /**
         * =========================
         * KYT SCORE
         * =========================
         */
        $score = 20;

        if ($changes >= 3) $score += 10;
        if ($changes >= 4) $score += 15;
        if ($changes >= 5) $score += 20;

        if ($unique >= 3) $score += 10;
        if ($unique >= 4) $score += 15;

        Log::warning('🔥 KYT FREQUENT BENEFICIARY CHANGES (BY PRODUCT)', [
            'produto' => $produto,
            'changes' => $changes,
            'unique_beneficiaries' => $unique,
            'score' => $score
        ]);

        $description = "

KYT_FREQUENT_BENEFICIARY_CHANGES

Produto: {$produto}
Cliente: {$customer->customer_number}

ANÁLISE DE COMPORTAMENTO

Beneficiários distintos: {$unique}
Alterações detetadas: {$changes}
Período: {$min->format('Y-m-d')} até {$max->format('Y-m-d')}
Duração: {$days} dias

INTERPRETAÇÃO KYT

Foi identificada alteração recorrente de beneficiários dentro do mesmo produto financeiro.

Este padrão pode indicar:
- Redirecionamento de benefícios dentro de produtos estruturados
- Substituição frequente sem justificação documental
- Tentativas de ocultação do beneficiário final (UBO)
- Potencial fase de layering em operações financeiras

De acordo com GAFI (2018), mudanças frequentes de beneficiários
em produtos financeiros são indicadores relevantes de risco AML/KYT,
especialmente quando ocorrem em períodos curtos (< 12 meses).

AÇÃO REQUERIDA
- Verificação documental de alterações de beneficiário
- Análise de relação entre titulares e beneficiários
- Avaliação de STR (Suspicious Transaction Report)
";

        $this->createAlert(
            $customer,
            'KYT_FREQUENT_BENEFICIARY_CHANGES',
            $description,
            'Alto',
            $score
        );
    }

    Log::info('KYT PRODUCT BENEFICIARY ANALYSIS FINISHED');
}

    /* =========================
       ALERT CREATION
    ========================== */

    private function createAlert(
        Entities $customer,
        string $type,
        string $description,
        string $severity,
        int $score
    ): void {

        $risk = $this->RiskAssessmentEntity($customer);

        $alert = Alert::updateOrCreate(
            [
                'entity_id' => $customer->id,
                'type' => $type,
                'description' => $description,
            ],
            [
                'alert_priority' => $risk['alert_priority'],
                'risk_assessment_id' => $risk['risk_id'],
                'category' => 'KYT',
                'level' => $severity,
                'name' => $customer->social_denomination,
                'score' => $score,
            ]
        );

        if ($alert->wasRecentlyCreated || $alert->wasChanged()) {
            SendGrupoAlertEmailJob::dispatch($alert->id, config('app.url'))
                ->onQueue('high');

            Log::warning("ALERT {$type}", [
                'customer' => $customer->customer_number
            ]);
        }
    }
}
