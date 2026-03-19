<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomerKYTService
{
    public function runAllChecksMemory(Entities $customer, array $policies): void
    {
        $policies = $this->normalizePolicies($policies);

        Log::info("🔍 KYT START", [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies)
        ]);

        $this->checkHighCapitalIncreaseMemory($customer, $policies);
        $this->checkEarlyRedemptionMemory($customer, $policies);
        $this->checkHighPremiumLowRiskMemory($customer, $policies);
        $this->checkMultipleShortPoliciesMemory($customer, $policies);
        $this->checkPolicyChurningMemory($customer, $policies);
        $this->checkRapidPolicyReplacementMemory($customer, $policies);
    }

    /* =========================
       NORMALIZAÇÃO
    ========================== */

    private function normalizePolicies(array $policies): array
    {
        return array_map(fn($p) => [
            'numero_apolice' => $p['numero_apolice'] ?? $p['contract_number'] ?? null,
            'numero_cliente' => $p['numero_cliente'] ?? $p['customer_number'] ?? null,
            'descricao_produto' => strtoupper(trim($p['descricao_produto'] ?? $p['product_desc'] ?? '')),
            'estado_apolice' => $this->normalizeStatus($p['estado_apolice'] ?? null),
            'data_inicio' => $p['data_inicio'] ?? $p['start_date'] ?? null,
            'data_fim' => $p['data_fim'] ?? $p['end_date'] ?? null,
            'capital' => (float)($p['capital'] ?? 0),
            'premium_total' => (float)($p['premium_total'] ?? 0),
            'interest' => (float)($p['interest'] ?? $p['juros'] ?? 0),
        ], $policies);
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtoupper(trim($status ?? ''));
        return match ($status) {
            'NORMAL' => 'active',
            'C/ CARTA' => 'cancelled',
            'ANULADA' => 'terminated',
            default => 'unknown'
        };
    }

    private function safeDays(?string $start, ?string $end): ?int
    {
        try {
            if (!$start || !$end) return null;
            return Carbon::parse($start)->diffInDays(Carbon::parse($end));
        } catch (\Exception $e) {
            return null;
        }
    }

    /* =========================
       REGRAS KYT
    ========================== */

    private function checkHighCapitalIncreaseMemory(Entities $customer, array $policies): void
    {
        $policies = array_filter($policies, fn($p) => !empty($p['data_inicio']));
        usort($policies, fn($a,$b) => strtotime($b['data_inicio']) - strtotime($a['data_inicio']));

        if (count($policies) < 2) return;

        $current  = $policies[0];
        $previous = $policies[1];

        if ($previous['capital'] <= 0) return;

        $daysBetween = $this->safeDays($previous['data_inicio'], $current['data_inicio']);
        if (!$daysBetween || $daysBetween > 60) return;

        $increaseRate = ($current['capital'] - $previous['capital']) / $previous['capital'];
        if ($increaseRate < 0.40) return;

        $premiumIncreaseRate = ($previous['premium_total'] > 0)
            ? ($current['premium_total'] - $previous['premium_total']) / $previous['premium_total']
            : 0;

        $disproportionateIncrease = $premiumIncreaseRate < ($increaseRate * 0.6);
        $noEconomicReturn = ($current['interest'] <= 0);

        $highRiskProducts = ['VIDA','POUPANÇA','UNIT LINKED','CAPITALIZAÇÃO','VIDA INDIVIDUAL','BAI CREDITO PESSOAL DIGITAL'];
        if (!in_array($current['descricao_produto'], $highRiskProducts)) return;

        if (!$disproportionateIncrease && !$noEconomicReturn) return;

        $description = "Apólice {$current['numero_apolice']} – aumento de capital suspeito";

        // NOVA NOMENCLATURA
        $this->createAlertOnce($customer, 'QuickPolicyReplacementDetected', $description, 'Alto', 'KYT', 30);
    }

    private function checkEarlyRedemptionMemory(Entities $customer, array $policies): void
    {
        foreach ($policies as $p) {
            if (!in_array($p['estado_apolice'], ['terminated','cancelled'])) continue;

            $days = $this->safeDays($p['data_inicio'], $p['data_fim']);
            if (!$days || $days >= 365) continue;

            $description = "Apólice {$p['numero_apolice']} cancelada em {$days} dias";

            $this->createAlertOnce($customer, 'EarlyRedemptionDetected', $description, 'Alto', 'KYT', 20);
        }
    }

    private function checkHighPremiumLowRiskMemory(Entities $customer, array $policies): void
    {
        foreach ($policies as $p) {
            if ($p['capital'] <= 0 || $p['premium_total'] <= 0) continue;

            $ratio = $p['premium_total'] / max($p['capital'],1);
            if ($ratio < 0.08) continue;

            $description = "Apólice {$p['numero_apolice']} – prémio elevado ({$ratio})";

            $this->createAlertOnce($customer, 'HighPremiumLowRisk', $description, 'Alto', 'KYT', 25);
        }
    }

    private function checkMultipleShortPoliciesMemory(Entities $customer, array $policies): void
    {
        $short = array_filter($policies, function($p) {
            $days = $this->safeDays($p['data_inicio'], $p['data_fim']);
            return $days && $days >= 90 && $days <= 180;
        });

        if (count($short) < 2) return;

        $description = count($short) . " apólices curtas";

        $this->createAlertOnce($customer, 'RepeatedReplacementOrCancellation', $description, 'Médio', 'KYT', 15);
    }

    private function checkPolicyChurningMemory(Entities $customer, array $policies): void
    {
        $terminated = array_filter($policies, fn($p) => in_array($p['estado_apolice'], ['terminated','cancelled']));

        if (count($terminated) < 2) return;

        $description = count($terminated) . " cancelamentos detectados";

        $this->createAlertOnce($customer, 'PolicyChurn', $description, 'Médio', 'KYT', 20);
    }

    private function checkRapidPolicyReplacementMemory(Entities $customer, array $policies): void
    {
        usort($policies, fn($a,$b) => strtotime($a['data_inicio']) - strtotime($b['data_inicio']));

        for ($i = 1; $i < count($policies); $i++) {
            $prev = $policies[$i-1];
            $curr = $policies[$i];

            if ($prev['estado_apolice'] !== 'terminated') continue;

            $gap = $this->safeDays($prev['data_fim'], $curr['data_inicio']);
            if (!$gap || $gap > 7) continue;

            $description = "Substituição rápida entre apólices";

            $this->createAlertOnce($customer, 'QuickPolicyReplacementDetected', $description, 'Médio', 'KYT', 15);
            break;
        }
    }

    /* =========================
       ALERTAS
    ========================== */

    private function createAlertOnce(
        Entities $customer,
        string $type,
        string $description,
        string $severity,
        string $list,
        int $score
    ): void {

        $alert = Alert::updateOrCreate(
            [
                'entity_id' => $customer->id,
                'type' => $type,
                'description' => trim($description),
            ],
            [
                'category' => 'KYT',
                'level' => $severity,
                'list' => $list,
                'name' => $customer->social_denomination,
                'score' => $score,
            ]
        );

        if ($alert->wasRecentlyCreated || $alert->wasChanged()) {
            SendGrupoAlertEmailJob::dispatch($alert->id, config('app.url'))->onQueue('high');

            Log::warning("🚨 ALERTA {$type}", [
                'cliente' => $customer->customer_number,
                'descricao' => $description
            ]);
        }
    }
}