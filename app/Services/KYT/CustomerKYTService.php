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
       RISK ASSESSMENT
    ========================== */

    public function RiskAssessmentEntity(Entities $customer): array
    {
        $cacheKey = "risk_assessment_entity_{$customer->id}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $risk = RiskAssessment::where('entity_id', $customer->id)->latest()->first();

        if (!$risk) {
            $data = [
                'risk_id' => null,
                'alert_priority' => false,
                'valid' => false
            ];

            Cache::put($cacheKey, $data, now()->addHours(20));
            return $data;
        }

        $isHighRisk = in_array($risk->diligence, ["Cliente Inaceitável", "Reforçada"]);

        $data = [
            'risk_id' => $risk->id,
            'alert_priority' => $isHighRisk,
            'valid' => !$isHighRisk
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

        $policies = $this->normalizePolicies($policies);

        Log::info("KYT START", [
            'customer' => $customer->customer_number,
            'policies' => count($policies)
        ]);

        if (empty($policies)) return;

        //   $this->checkHighCapitalIncrease($customer, $changes);
        //   $this->checkEarlyRedemption($customer, $policies, $refunds);
        //   $this->checkHighPremium($customer, $policies);
        //   $this->checkMultipleShortPolicies($customer, $policies);
        //  $this->checkPolicyChurning($customer, $policies);
        //  $this->checkRapidReplacement($customer, $policies);
        //   $this->checkThirdPartyPayments($customer, $policies);
        // $this->checkOverpaymentRefund($customer, $policies, $receipts, $refunds);
        $this->checkFrequentBeneficiaryChanges($customer, $receipts);

        Log::info("KYT FINISHED", [
            'customer' => $customer->customer_number
        ]);
    }

    /* =========================
       NORMALIZATION
    ========================== */

    private function normalizePolicies(array $policies): array
    {
        return array_map(function ($p) {
            return [
                'numero_apolice' => $p['Numero_Apolice'] ?? $p['numero_apolice'] ?? null,
                'descricao_produto' => strtoupper(trim($p['Descricao_Produto'] ?? '')),
                'estado_apolice' => $this->normalizeStatus($p['Estado_Apolice'] ?? null),
                'TOMADOR' => $this->normalizeStatus($p['TOMADOR'] ?? null),
                'data_inicio' => $this->parseDate($p['Data_Inicio'] ?? null),
                'data_fim' => $this->parseDate($p['Data_Fim'] ?? null),
                'data_anulacao' => $p['Data_Anulacao'] ?? null,
                'capital' => (float)($p['Capital'] ?? 0),
                'premium_total' => (float)($p['Premio_Total'] ?? 0),
                'interest' => (float)($p['Juros'] ?? 0),
            ];
        }, $policies);
    }

    private function normalizeStatus(?string $status): string
    {
        return match (strtoupper(trim($status ?? ''))) {
            'NORMAL', 'ATIVA' => 'active',
            'CANCELADA', 'C/ CARTA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS' => 'terminated',
            default => 'unknown'
        };
    }

    private function parseDate($date): ?string
    {
        if (!$date) return null;

        try {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function safeDate($date)
    {
        try {
            if (!$date) return null;
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatMoney($value): string
    {
        return number_format((float)$value, 2, '.', ' ');
    }

    /* =========================
       RULES
    ========================== */

    private function checkHighCapitalIncrease(Entities $customer, array $changes): void
    {
        foreach ($changes as $change) {

            $old = (float)($change->valor_anterior ?? 0);
            $new = (float)($change->novo_valor ?? 0);

            if ($old <= 0) continue;

            $increase = ($new - $old) / $old;

            if ($increase < 0.30) continue;

            $this->createAlert(
                $customer,
                "Aumento de capital elevado",
                "Apólice {$change->numero_apolice} aumento de " . ($increase * 100) . "%",
                "Alto",
                20
            );
        }
    }

    private function checkEarlyRedemption(Entities $customer, array $policies, array $refunds = []): void
    {
        foreach ($policies as $p) {

            if (!in_array($p['estado_apolice'], ['cancelled', 'terminated'])) continue;

            $start = $this->safeDate($p['data_inicio']);
            $end = $this->safeDate($p['data_fim'] ?? $p['data_anulacao']);

            if (!$start || !$end) continue;

            $days = $start->diffInDays($end);

            if ($days <= 0 || $days >= 365) continue;

            $paid = (float)$p['premium_total'];

            if ($paid <= 0) continue;

            $this->createAlert(
                $customer,
                "Resgate antecipado",
                "Apólice {$p['numero_apolice']} resgatada em {$days} dias",
                "Alto",
                20
            );
        }
    }

    private function checkHighPremium(Entities $customer, array $policies): void
    {
        foreach ($policies as $p) {

            if ($p['capital'] <= 0 || $p['premium_total'] <= 0) continue;

            $ratio = $p['premium_total'] / $p['capital'];

            if ($ratio < 0.08) continue;

            $this->createAlert(
                $customer,
                "Prémio elevado",
                "Apólice {$p['numero_apolice']} ratio {$ratio}",
                "Alto",
                25
            );
        }
    }

    private function checkMultipleShortPolicies(Entities $customer, array $policies): void
    {
        $valid = [];

        foreach ($policies as $p) {
            $start = $this->safeDate($p['data_inicio']);
            $end = $this->safeDate($p['data_fim']);

            if (!$start || !$end) continue;

            if ($start->diffInDays($end) >= 90 && $start->diffInDays($end) <= 180) {
                $valid[] = $p;
            }
        }

        if (count($valid) < 3) return;

        $this->createAlert(
            $customer,
            "Múltiplas apólices curtas",
            "Detectado padrão de fragmentação",
            "Médio",
            15
        );
    }

    private function checkPolicyChurning(Entities $customer, array $policies): void
    {
        $count = 0;

        foreach ($policies as $p) {
            if (in_array($p['estado_apolice'], ['cancelled', 'terminated'])) {
                $count++;
            }
        }

        if ($count < 3) return;

        $this->createAlert(
            $customer,
            "Churn de apólices",
            "Cancelamentos frequentes detectados",
            "Médio",
            20
        );
    }

    private function checkRapidReplacement(Entities $customer, array $policies): void
    {
        if (count($policies) < 3) return;

        $this->createAlert(
            $customer,
            "Substituição rápida",
            "Possível troca rápida de apólices",
            "Alto",
            15
        );
    }

    private function checkThirdPartyPayments(Entities $customer, array $policies): void
    {
        foreach ($policies as $p) {
            if (($p['premium_total'] ?? 0) > 100000) {
                $this->createAlert(
                    $customer,
                    "Pagamento terceiro",
                    "Pagamento elevado detectado",
                    "Alto",
                    25
                );
            }
        }
    }

    private function checkOverpaymentRefund(
        Entities $customer,
        array $policies,
        array $receipts = [],
        array $refunds = []
    ): void {

        foreach ($receipts as $r) {

            $paid = (float)($r['Valor_Pago'] ?? 0);
            if ($paid <= 0) continue;

            $policy = collect($policies)
                ->firstWhere('numero_apolice', $r['Numero_Apolice'] ?? null);

            if (!$policy) continue;

            $expected = (float)($policy['premium_total'] ?? 0);
            if ($expected <= 0) continue;

            if (($paid / $expected) < 1.5) continue;

            $this->createAlert(
                $customer,
                "Sobrepagamento com reembolso",
                "Apólice {$policy['numero_apolice']}",
                "Alto",
                20
            );
        }
    }

private function checkFrequentBeneficiaryChanges(
    Entities $customer,
    array $receipts = []
): void {

    Log::info('KYT DEBUG RECEIPT SAMPLE', [
    'first' => $receipts[0] ?? null
]);

    if (empty($receipts)) return;

    $grouped = collect($receipts)
        ->map(fn($p) => (array) $p) // 🔥 FIX CRÍTICO
        ->groupBy('numero_apolice');

    foreach ($grouped as $apolice => $payments) {

        if ($payments->count() < 3) continue;

        $payments = $payments->sortBy('data_pagamento')->values();

        $beneficiaries = [];
        $changes = 0;
        $prev = null;

        foreach ($payments as $p) {

            $current = trim(
                ($p['nif_pagador'] ?? '') . '|' .
                ($p['nome_pagador'] ?? '')
            );

            $beneficiaries[$current] = true;

            if ($prev !== null && $prev !== $current) {
                $changes++;
            }

            $prev = $current;
        }

        $uniqueCount = count($beneficiaries);

        if ($changes < 3) continue;
        if ($uniqueCount < 3) continue;

        $dates = $payments->map(fn($p) => $this->safeDate($p['data_pagamento']))
            ->filter();

        if ($dates->isEmpty()) continue;

        $min = $dates->min();
        $max = $dates->max();

        if ($min->diffInDays($max) > 365) continue;

        $score = 20 + ($changes >= 4 ? 5 : 0);

        $this->createAlert(
            $customer,
            'KYT_FREQUENT_BENEFICIARY_CHANGES',
            "Apólice {$apolice} | mudanças {$changes} | beneficiários {$uniqueCount}",
            'Alto',
            $score
        );
    }
}
    /* =========================
       ALERT
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
            SendGrupoAlertEmailJob::dispatch($alert->id, config('app.url'))->onQueue('high');

            Log::warning("ALERT {$type}", [
                'customer' => $customer->customer_number
            ]);
        }
    }
}
