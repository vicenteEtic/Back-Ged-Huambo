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

        $data = [
            'risk_id' => $risk->id ?? null,
            'alert_priority' => $risk ? in_array($risk->diligence, ["Cliente Inaceitável", "Reforçada"]) : false,
            'valid' => $risk ? false : true,
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
        $receipts = $this->normalizeArray($receipts);
        $changes  = $this->normalizeArray($changes);
        $refunds  = $this->normalizeArray($refunds);

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
       NORMALIZERS (ANTI CRASH CORE)
    ========================== */

    private function normalizeArray(array $data): array
    {
        return array_values(array_filter(array_map(function ($item) {
            return is_array($item) ? $item : (array) $item;
        }, $data)));
    }

    private function normalizePolicies(array $policies): array
    {
        return $this->normalizeArray(array_map(function ($p) {

            $p = (array) $p;

            return [
                'numero_apolice' => $p['Numero_Apolice'] ?? $p['numero_apolice'] ?? null,
                'estado_apolice' => $this->normalizeStatus($p['Estado_Apolice'] ?? null),
                'data_inicio' => $this->parseDate($p['Data_Inicio'] ?? null),
                'data_fim' => $this->parseDate($p['Data_Fim'] ?? null),
                'capital' => (float) ($p['Capital'] ?? 0),
                'premium_total' => (float) ($p['Premio_Total'] ?? 0),
            ];

        }, $policies));
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

    private function safeDate($date): ?Carbon
    {
        try {
            if (!$date) return null;
            return Carbon::parse($date);
        } catch (\Exception $e) {
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

        Log::info('KYT DEBUG RECEIPT SAMPLE', [
            'first' => $receipts[0] ?? null
        ]);

        $grouped = collect($this->normalizeArray($receipts))
            ->groupBy('numero_apolice');

        foreach ($grouped as $apolice => $payments) {

            if (!$apolice || $payments->count() < 3) continue;

            $payments = $payments
                ->map(function ($p) {
                    $p['parsed_date'] = $this->safeDate($p['data_pagamento'] ?? null);
                    return $p;
                })
                ->filter(fn($p) => $p['parsed_date'] !== null)
                ->sortBy('parsed_date')
                ->values();

            if ($payments->count() < 3) continue;

            $beneficiaries = [];
            $changes = 0;
            $prev = null;

            foreach ($payments as $p) {

                $current = trim(
                    ($p['nif_pagador'] ?? '') . '|' .
                    ($p['nome_pagador'] ?? '')
                );

                if ($current === '|') continue;

                $beneficiaries[$current] = true;

                if ($prev !== null && $prev !== $current) {
                    $changes++;
                }

                $prev = $current;
            }

            $uniqueCount = count($beneficiaries);

            if ($uniqueCount < 3) continue;
            if ($changes < 2) continue;

            $dates = $payments->pluck('parsed_date');

            $min = $dates->first();
            $max = $dates->last();

            if (!$min || !$max) continue;

            if ($min->diffInDays($max) > 365) continue;

            $score = 20;

            if ($uniqueCount >= 4) $score += 5;
            if ($changes >= 3) $score += 5;
            if ($changes >= 5) $score += 10;


            $description = "


IDENTIFICAÇÃO
- Cliente: {$customer->customer_number}
- Apólice: {$apolice}
- Total de transações analisadas: {$payments->count()}

PADRÃO DETECTADO
- Beneficiários únicos identificados: {$uniqueCount}
- Número de alterações entre beneficiários: {$changes}
- Período de análise: {$min->format('Y-m-d')} até {$max->format('Y-m-d')}
- Duração total do comportamento: {$min->diffInDays($max)} dias

ANÁLISE COMPORTAMENTAL
Foi identificado um padrão consistente de alterações frequentes de beneficiários associados a esta apólice, com substituição repetida de entidades pagadoras (pessoas ou organizações).

As alterações não apresentam justificação operacional evidente (ex: herança, divórcio, alteração contratual documentada ou atualização de dados cadastrais).

RISCO AML/KYT
Este comportamento pode indicar:
- Tentativa de ocultação da origem ou destino dos fundos
- Estruturação de pagamentos através de terceiros
- Possível layering financeiro
- Fragmentação de beneficiários para dificultar rastreio

REFERENCIAL REGULATÓRIO
Este padrão está alinhado com indicadores de risco descritos pelo GAFI (Guidance 2018), nomeadamente:
- Alterações frequentes de beneficiários em fase de movimentação financeira
- Redirecionamento recorrente de pagamentos para terceiros sem relação clara com o tomador

CLASSIFICAÇÃO
- Score KYT: {$score}
- Nível de risco: Alto
";

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
       ALERT SYSTEM
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