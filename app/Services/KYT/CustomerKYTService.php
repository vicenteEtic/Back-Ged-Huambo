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

            // 🔥 DESCRIÇÃO MELHORADA
            $description = "
KYT ALERT - ALTERAÇÕES FREQUENTES DE PAGADOR

══════════════════════════════
IDENTIFICAÇÃO
══════════════════════════════
Cliente: {$customer->customer_number}
Apólice: {$apolice}
Transações: {$payments->count()}

══════════════════════════════
ANÁLISE
══════════════════════════════
Pagadores distintos: {$uniqueCount}
Mudanças detectadas: {$changes}
Período: {$min->format('Y-m-d')} até {$max->format('Y-m-d')}
Duração: {$days} dias

══════════════════════════════
INTERPRETAÇÃO AML
══════════════════════════════
Foi identificado um padrão de múltiplos pagadores associados à mesma apólice.

Possíveis riscos:
- Utilização de terceiros para pagamento
- Ocultação do beneficiário real
- Estruturação de fluxos financeiros (layering)
- Fragmentação de valores

Referência: GAFI/FATF (2018)

══════════════════════════════
CLASSIFICAÇÃO
══════════════════════════════
Score: {$score}
Nível: Alto
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
