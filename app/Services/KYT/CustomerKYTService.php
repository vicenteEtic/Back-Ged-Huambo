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
        ->filter(function ($r) {
            return !empty($r['numero_apolice']);
        })
        ->groupBy('numero_apolice');

    Log::info('KYT GROUPED', [
        'policies_grouped' => $grouped->count()
    ]);

    foreach ($grouped as $apolice => $payments) {

        Log::info('KYT POLICY CHECK', [
            'apolice' => $apolice,
            'payments' => $payments->count()
        ]);

        if ($payments->count() < 3) {
            Log::info('KYT SKIP - LESS THAN 3 PAYMENTS', [
                'apolice' => $apolice
            ]);
            continue;
        }

        $payments = $payments->sortBy(function ($p) {
            return $this->safeDate($p['data_pagamento'])?->timestamp ?? 0;
        })->values();

        $beneficiaries = [];
        $changes = 0;
        $prev = null;

        foreach ($payments as $p) {

            $nif = trim($p['nif_pagador'] ?? '');
            $name = trim($p['nome_pagador'] ?? '');

            $current = $nif . '|' . $name;

            Log::info('KYT PAYMENT STEP', [
                'apolice' => $apolice,
                'current' => $current
            ]);

            if ($current === '|') {
                Log::info('KYT SKIP EMPTY BENEFICIARY');
                continue;
            }

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
            'changes' => $changes
        ]);

        if ($uniqueCount < 3) {
            Log::info('KYT SKIP - UNIQUE < 3');
            continue;
        }

        if ($changes < 2) {
            Log::info('KYT SKIP - CHANGES < 2');
            continue;
        }

        $dates = $payments->map(fn($p) => $this->safeDate($p['data_pagamento']))
            ->filter();

        if ($dates->isEmpty()) {
            Log::info('KYT SKIP - NO VALID DATES');
            continue;
        }

        $min = $dates->sort()->first();
        $max = $dates->sort()->last();

        if (!$min || !$max) {
            Log::info('KYT SKIP - INVALID DATE RANGE');
            continue;
        }

        $days = $min->diffInDays($max);

        Log::info('KYT TIME WINDOW', [
            'apolice' => $apolice,
            'days' => $days
        ]);

        if ($days > 365) {
            Log::info('KYT SKIP - PERIOD > 365 DAYS');
            continue;
        }

        $score = 20;

        if ($uniqueCount >= 4) $score += 5;
        if ($changes >= 3) $score += 5;
        if ($changes >= 5) $score += 10;

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
- Transações analisadas: {$payments->count()}

PADRÃO DETECTADO
- Beneficiários únicos: {$uniqueCount}
- Alterações de beneficiários: {$changes}
- Período: {$min->format('Y-m-d')} → {$max->format('Y-m-d')}
- Duração: {$min->diffInDays($max)} dias

ANÁLISE
Foi identificado padrão de alterações recorrentes de beneficiários sem justificação operacional clara.

RISCO AML
- Possível layering financeiro
- Redirecionamento de pagamentos via terceiros
- Tentativa de ocultação de origem/destino de fundos

REFERÊNCIA GAFI (2018)
Alterações frequentes de beneficiários em fase de movimentação financeira são indicadores clássicos de risco AML.

SCORE KYT: {$score}
";

        $this->createAlert(
            $customer,
            'KYT_FREQUENT_BENEFICIARY_CHANGES',
             $description ,
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