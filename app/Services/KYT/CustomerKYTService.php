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
        array $receipts = []
    ): void {

        Log::info("KYT START", [
            'customer' => $customer->customer_number,
            'policies' => count($policies)
        ]);

        if (empty($policies)) return;

        $this->checkFrequentBeneficiaryChanges($customer, $receipts);

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
        array $receipts = []
    ): void {

        Log::info('KYT RECEIPTS START', [
            'customer' => $customer->customer_number,
            'receipts_count' => count($receipts)
        ]);

        if (empty($receipts)) {
            Log::info('KYT EXIT - EMPTY RECEIPTS');
            return;
        }

        $grouped = collect($receipts)
            ->map(fn($r) => (array) $r)
            ->filter(fn($r) => !empty($r['numero_apolice']))
            ->groupBy('numero_apolice');

        foreach ($grouped as $apolice => $payments) {

            if ($payments->count() < 2) continue;

            $payments = $payments->sortBy(
                fn($p) =>
                $this->safeDate($p['data_pagamento'])?->timestamp ?? 0
            )->values();

            $beneficiaries = [];
            $changes = 0;
            $prev = null;

            foreach ($payments as $p) {

                // 🔥 NORMALIZAÇÃO (CRÍTICO)
                $nif = strtoupper(trim((string) ($p['nif_pagador'] ?? '')));
                $name = strtoupper(trim((string) ($p['nome_pagador'] ?? '')));

                if ($nif === '' && $name === '') continue;

                // 🔥 ID único consistente
                $current = $nif ?: md5($name);

                $beneficiaries[$current] = true;

                if ($prev !== null && $prev !== $current) {
                    $changes++;
                }

                $prev = $current;
            }

            $uniqueCount = count($beneficiaries);

            if ($uniqueCount < 2 || $changes < 1) continue;

            $dates = $payments->map(
                fn($p) =>
                $this->safeDate($p['data_pagamento'])
            )->filter();

            if ($dates->isEmpty()) continue;

            $min = $dates->min();
            $max = $dates->max();

            if (!$min || !$max) continue;

            $days = $min->diffInDays($max);

            if ($days > 730) continue;

            // 🔥 SCORE MELHORADO
            $score = 20;

            if ($uniqueCount >= 3) $score += 10;
            if ($uniqueCount >= 4) $score += 15;
            if ($changes >= 2) $score += 10;
            if ($changes >= 3) $score += 15;

            Log::warning('🔥 KYT ALERT TRIGGERED', [
                'apolice' => $apolice,
                'score' => $score
            ]);


            $totalPago = $payments->sum(fn($p) => max(0, (float)($p['valor_pago'] ?? 0)));
            $totalEstornado = $payments->sum(fn($p) => min(0, (float)($p['valor_pago'] ?? 0)));
            $saldo = $payments->sum(fn($p) => (float)($p['valor_pago'] ?? 0));

            // 🔥 lista de pagadores (limpa e única)
            $payersList = $payments->map(function ($p) {
                return strtoupper(trim((string)($p['nome_pagador'] ?? '')));
            })
                ->filter()
                ->unique()
                ->values()
                ->take(5) // evita texto gigante
                ->implode(', ');
            // 🔥 DESCRIÇÃO MELHORADA
        $description = "
KYT ALERT - ALTERAÇÕES FREQUENTES DE PAGADOR

══════════════════════════════
IDENTIFICAÇÃO
══════════════════════════════
Cliente: {$customer->customer_number}
Apólice: {$apolice}
Total de Transações: {$payments->count()}

══════════════════════════════
ANÁLISE FINANCEIRA
══════════════════════════════
Valor total pago: " . number_format($totalPago, 2, ',', '.') . "
Valor total estornado: " . number_format($totalEstornado, 2, ',', '.') . "
Saldo líquido: " . number_format($saldo, 2, ',', '.') . "

══════════════════════════════
ANÁLISE COMPORTAMENTAL
══════════════════════════════
Pagadores distintos: {$uniqueCount}
Mudanças detectadas: {$changes}
Principais pagadores: {$payersList}

Período analisado: {$min->format('Y-m-d')} até {$max->format('Y-m-d')}
Duração: {$days} dias

══════════════════════════════
PADRÃO DETECTADO
══════════════════════════════
Foi identificado um padrão de múltiplos pagadores associados à mesma apólice, com alterações recorrentes ao longo do tempo.

Este comportamento pode indicar:
- Utilização de terceiros para execução de pagamentos
- Redirecionamento de fluxos financeiros
- Fragmentação de valores para evitar controlo
- Possível ocultação do beneficiário final (UBO)

══════════════════════════════
AVALIAÇÃO DE RISCO AML
══════════════════════════════
A alternância de pagadores, combinada com movimentação financeira relevante, constitui um indicador típico de risco em processos de monitorização KYT.

Referência: GAFI/FATF (2018) - Risk-Based Approach Guidance

══════════════════════════════
CLASSIFICAÇÃO
══════════════════════════════
Score KYT: {$score}
Nível de Risco: Alto
Tipo de Evento: Alterações Frequentes de Pagador
";

            $this->createAlert(
                $customer,
                'Alterações frequentes de beneficiários', // 🔥 nome mais correto
                $description,
                'Alto',
                $score
            );
        }

        Log::info('KYT FINISHED CHECK');
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
