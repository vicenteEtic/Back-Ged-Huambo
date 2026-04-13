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

        if (empty($receipts)) return;

        $grouped = collect($receipts)
            ->map(fn($r) => (array) $r)
            ->filter(fn($r) => !empty($r['numero_apolice']))
            ->groupBy('numero_apolice');

        foreach ($grouped as $apolice => $payments) {

            if ($payments->count() < 3) continue;

            // ordenar seguro
            $payments = $payments->sortBy(function ($p) {
                return $this->safeDate($p['data_pagamento'])?->timestamp ?? 0;
            })->values();

            $beneficiaries = [];
            $changes = 0;
            $prev = null;

            foreach ($payments as $p) {

                $nif = trim($p['nif_pagador'] ?? '');
                $name = trim($p['nome_pagador'] ?? '');

                if ($nif === '' && $name === '') continue;

                $current = $nif . '|' . $name;

                $beneficiaries[$current] = true;

                if ($prev !== null && $prev !== $current) {
                    $changes++;
                }

                $prev = $current;
            }

            $uniqueCount = count($beneficiaries);

            if ($uniqueCount < 3 && $changes < 3) continue;

            $dates = $payments->map(fn($p) => $this->safeDate($p['data_pagamento']))->filter();

            if ($dates->isEmpty()) continue;

            $min = $dates->sort()->first();
            $max = $dates->sort()->last();

            if (!$min || !$max) continue;

            if ($min->diffInDays($max) > 365) continue;

            /* =========================
               SCORE
            ========================== */

            $score = 20;

            if ($uniqueCount >= 4) $score += 5;
            if ($changes >= 3) $score += 5;
            if ($changes >= 5) $score += 10;

            /* =========================
               DESCRIPTION AML
            ========================== */

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

            /* =========================
               ALERT
            ========================== */

            $this->createAlert(
                $customer,
                'KYT_FREQUENT_BENEFICIARY_CHANGES',
                $description,
                'Alto',
                $score
            );
        }
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