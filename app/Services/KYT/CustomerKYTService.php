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

    Log::info('KYT GROUPED', [
        'policies' => $grouped->count()
    ]);

    foreach ($grouped as $apolice => $payments) {

        Log::info('KYT POLICY CHECK', [
            'apolice' => $apolice,
            'payments' => $payments->count()
        ]);

        // 🔥 AJUSTE IMPORTANTE: não ser tão rígido
        if ($payments->count() < 2) {
            Log::info('KYT SKIP - NOT ENOUGH PAYMENTS', [
                'apolice' => $apolice
            ]);
            continue;
        }

        // 🔥 ordenar corretamente
        $payments = $payments->sortBy(function ($p) {
            return $this->safeDate($p['data_pagamento'])?->timestamp ?? 0;
        })->values();

        $beneficiaries = [];
        $changes = 0;
        $prev = null;

        foreach ($payments as $p) {

            $nif = trim((string) ($p['nif_pagador'] ?? ''));
            $name = trim((string) ($p['nome_pagador'] ?? ''));

            // 🔥 evita lixo total
            if ($nif === '' && $name === '') {
                Log::info('KYT SKIP EMPTY ROW');
                continue;
            }

            $current = $nif . '|' . $name;

            Log::info('KYT PAYMENT STEP', [
                'apolice' => $apolice,
                'current' => $current
            ]);

            $beneficiaries[$current] = true;

            if ($prev !== null && $prev !== $current) {
                $changes++;
            }

            $prev = $current;
        }

        $uniqueCount = count($beneficiaries);

        Log::info('KYT SUMMARY', [
            'apolice' => $apolice,
            'unique_beneficiaries' => $uniqueCount,
            'changes' => $changes,
            'payments' => $payments->count()
        ]);

        // 🔥 AJUSTE CRÍTICO (ANTES ESTAVA MUITO RESTRITO)
        if ($uniqueCount < 2) {
            Log::info('KYT SKIP - ONLY ONE BENEFICIARY');
            continue;
        }

        if ($changes < 1) {
            Log::info('KYT SKIP - NO CHANGES DETECTED');
            continue;
        }

        // 📅 janela temporal
        $dates = $payments->map(fn($p) => $this->safeDate($p['data_pagamento']))
            ->filter();

        if ($dates->isEmpty()) {
            Log::info('KYT SKIP - NO VALID DATES');
            continue;
        }

        $min = $dates->min();
        $max = $dates->max();

        if (!$min || !$max) {
            Log::info('KYT SKIP - INVALID DATES');
            continue;
        }

        $days = $min->diffInDays($max);

        Log::info('KYT TIME WINDOW', [
            'apolice' => $apolice,
            'days' => $days
        ]);

        if ($days > 730) { // 🔥 2 anos (mais realista)
            Log::info('KYT SKIP - PERIOD TOO LONG');
            continue;
        }

        // 🔥 SCORE mais sensível (IMPORTANTE PARA ALERTAR MAIS)
        $score = 15;

        if ($uniqueCount >= 3) $score += 5;
        if ($uniqueCount >= 4) $score += 10;
        if ($changes >= 2) $score += 5;
        if ($changes >= 3) $score += 10;

        Log::warning('🔥 KYT ALERT TRIGGERED', [
            'apolice' => $apolice,
            'score' => $score,
            'changes' => $changes,
            'unique' => $uniqueCount
        ]);

        $description = "
KYT - FREQUENT BENEFICIARY CHANGES

IDENTIFICAÇÃO
- Cliente: {$customer->customer_number}
- Apólice: {$apolice}
- Transações: {$payments->count()}

PADRÃO DETECTADO
- Beneficiários únicos: {$uniqueCount}
- Mudanças detectadas: {$changes}
- Período: {$min->format('Y-m-d')} → {$max->format('Y-m-d')}
- Duração: {$days} dias

INTERPRETAÇÃO AML
Alterações de beneficiários indicam possível:
- Layering financeiro
- Fragmentação de pagamentos
- Redirecionamento de fundos via terceiros
- Tentativa de dissimulação de beneficiário real

REFERÊNCIA GAFI (2018)
Mudanças frequentes de beneficiários em fases de movimentação financeira são indicadores relevantes de risco AML.

SCORE KYT: {$score}
";

        $this->createAlert(
            $customer,
            'KYT_FREQUENT_BENEFICIARY_CHANGES',
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