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

        Log::info("🚀 KYT START", [
            'customer' => $customer->customer_number,
            'policies' => count($policies)
        ]);

        if (empty($policies)) return;

        $this->checkFrequentBeneficiaryChanges($customer, $beneficiaries);

        Log::info("🏁 KYT FINISHED", [
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

        Log::info('🚀 KYT PRODUCT BENEFICIARY ANALYSIS START', [
            'customer' => $customer->customer_number,
            'records_received' => count($beneficiaries)
        ]);

        if (empty($beneficiaries)) {
            Log::warning('⚠️ KYT EXIT - EMPTY BENEFICIARIES');
            return;
        }

        /* =========================
           NORMALIZAÇÃO CRÍTICA
        ========================== */

        $beneficiaries = collect($beneficiaries)
            ->map(function ($b) {

                $b = (array) $b;

                return [
                    'numero_apolice' => trim((string)($b['numero_apolice'] ?? '')),
                    'descricao_produto' => strtoupper(trim($b['descricao_produto'] ?? 'UNKNOWN')),
                    'codigo_beneficiario' => trim((string)($b['codigo_beneficiario'] ?? '')),
                    'nome_beneficiario' => strtoupper(trim($b['nome_beneficiario'] ?? '')),
                    'tipo_beneficiario' => strtoupper(trim($b['tipo_beneficiario'] ?? '')),
                    'data' => $b['data_atualizacao_beneficiario'] ?? null,
                ];
            })
            ->filter(fn($b) => $b['numero_apolice'] !== '')
            ->values();

        Log::info('📦 NORMALIZED BENEFICIARIES', [
            'count' => $beneficiaries->count()
        ]);

        /* =========================
           GROUP BY PRODUCT
        ========================== */

        $grouped = $beneficiaries->groupBy('descricao_produto');

        Log::info('📊 GROUPS CREATED', [
            'total_groups' => $grouped->count(),
            'products' => $grouped->keys()
        ]);

        foreach ($grouped as $produto => $records) {

            Log::info('🔎 PROCESSING PRODUCT', [
                'produto' => $produto,
                'records' => $records->count()
            ]);

            if ($records->count() < 2) {
                Log::info('⛔ SKIP PRODUCT (INSUFFICIENT DATA)', [
                    'produto' => $produto
                ]);
                continue;
            }

            $records = $records->sortBy(fn($r) =>
                $this->safeDate($r['data'])?->timestamp ?? 0
            )->values();

            $history = [];
            $changes = 0;
            $prev = null;

            foreach ($records as $r) {

                $beneficiaryId = $r['codigo_beneficiario']
                    ?: md5($r['nome_beneficiario'] . $r['tipo_beneficiario']);

                $history[] = $beneficiaryId;

                if ($prev !== null && $prev !== $beneficiaryId) {
                    $changes++;
                }

                $prev = $beneficiaryId;
            }

            $unique = count(array_unique($history));

            Log::info('📈 ANALYSIS RESULT', [
                'produto' => $produto,
                'unique_beneficiaries' => $unique,
                'changes' => $changes
            ]);

            if ($unique < 3 || $changes < 2) {
                Log::info('⛔ RULE NOT TRIGGERED', [
                    'produto' => $produto
                ]);
                continue;
            }

            $dates = $records->map(fn($r) =>
                $this->safeDate($r['data'])
            )->filter();

            if ($dates->isEmpty()) {
                Log::warning('❌ NO VALID DATES', [
                    'produto' => $produto
                ]);
                continue;
            }

            $min = $dates->min();
            $max = $dates->max();
            $days = $min->diffInDays($max);

            if ($days > 365) {
                Log::info('⛔ TIME RANGE EXCEEDED', [
                    'produto' => $produto,
                    'days' => $days
                ]);
                continue;
            }

            /* =========================
               SCORE KYT
            ========================== */

            $score = 20;

            if ($changes >= 3) $score += 10;
            if ($changes >= 4) $score += 15;
            if ($changes >= 5) $score += 20;

            if ($unique >= 3) $score += 10;
            if ($unique >= 4) $score += 15;

            Log::warning('🚨 KYT ALERT TRIGGERED', [
                'produto' => $produto,
                'score' => $score
            ]);
$beneficiaryList = collect($records)
    ->map(function ($r) {
        return "- Nome: {$r['nome_beneficiario']}
  Tipo: {$r['tipo_beneficiario']}
  ID Beneficiário: {$r['codigo_beneficiario']}
  Apólice: {$r['numero_apolice']}
  Percentagem: {$r['percentagem_atribuida']}%";
    })
    ->implode("\n\n");

$apolicesUnicas = $records->pluck('numero_apolice')->unique()->implode(', ');

$description = "
KYT - ALTERAÇÃO FREQUENTE DE BENEFICIÁRIOS

Cliente: {$customer->customer_number}
Produto: {$produto}
Apólices envolvidas: {$apolicesUnicas}

📊 RESUMO DO RISCO:
- Beneficiários distintos: {$unique}
- Número de alterações: {$changes}
- Período analisado: {$min->format('Y-m-d')} → {$max->format('Y-m-d')}
- Duração: {$days} dias

👥 BENEFICIÁRIOS IDENTIFICADOS:
{$beneficiaryList}

⚠️ ANÁLISE DE RISCO:
Foi identificado um padrão de alterações de beneficiários dentro do mesmo produto/apólice.
Este comportamento pode indicar redistribuição frequente de capital segurado ou tentativa de ocultação de beneficiário final (UBO).

📌 RECOMENDAÇÃO:
Solicitar validação documental das alterações e justificar a relação entre os beneficiários e o segurado.

Referência: GAFI 2018 - Beneficial Ownership Risk Indicators
";

            $this->createAlert(
                $customer,
                'KYT_FREQUENT_BENEFICIARY_CHANGES',
                $description,
                'Alto',
                $score
            );
        }

        Log::info('🏁 KYT PRODUCT BENEFICIARY ANALYSIS FINISHED');
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

            Log::warning("ALERT CREATED", [
                'type' => $type,
                'customer' => $customer->customer_number
            ]);
        }
    }
}