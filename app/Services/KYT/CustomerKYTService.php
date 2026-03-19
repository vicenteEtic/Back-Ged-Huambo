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

        $this->checkHighCapitalIncreaseMemory($customer, $policies);      // +30 pts
        $this->checkEarlyRedemptionMemory($customer, $policies);          // +20 pts
        $this->checkHighPremiumLowRiskMemory($customer, $policies);       // +25 pts
        $this->checkMultipleShortPoliciesMemory($customer, $policies);    // +15 pts
        $this->checkPolicyChurningMemory($customer, $policies);           // +20 pts
        $this->checkRapidPolicyReplacementMemory($customer, $policies);   // +15 pts
    }

    private function normalizePolicies(array $policies): array
    {
        return array_map(fn($p) => [
            'numero_apolice' => $p['numero_apolice'] ?? null,
            'numero_cliente' => $p['numero_cliente'] ?? null,
            'descricao_produto' => strtoupper(trim($p['descricao_produto'] ?? '')),
            'estado_apolice' => $p['estado_apolice'] ?? null,
            'data_inicio' => $p['data_inicio'] ?? null,
            'data_fim' => $p['data_fim'] ?? null,
            'capital' => (float)($p['capital'] ?? 0),
            'premium_total' => (float)($p['premium_total'] ?? 0),
            'interest' => (float)($p['interest'] ?? 0),
        ], $policies);
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

        if (($previous['capital'] ?? 0) <= 0) return;

        $daysBetween = Carbon::parse($current['data_inicio'])
            ->diffInDays(Carbon::parse($previous['data_inicio']));
        if ($daysBetween > 60) return;

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

        $description = sprintf(
            "Apólice %s (%s) – Capital aumentou %.2f%% em %d dias (%s → %s)",
            $current['numero_apolice'],
            $current['descricao_produto'],
            $increaseRate*100,
            $daysBetween,
            number_format($previous['capital'],2,',','.'),
            number_format($current['capital'],2,',','.')
        );

        $this->createAlertOnce($customer, 'KYT_HIGH_CAPITAL_INCREASE', $description, 'Alto', 'KYT', 30);
    }

    private function checkEarlyRedemptionMemory(Entities $customer, array $policies): void
    {
        foreach ($policies as $p) {
            if (!in_array($p['estado_apolice'], ['terminated','cancelled'])) continue;
            if (empty($p['data_inicio']) || empty($p['data_fim'])) continue;

            $days = Carbon::parse($p['data_inicio'])->diffInDays(Carbon::parse($p['data_fim']));
            if ($days >= 365) continue;

            $description = "Apólice {$p['numero_apolice']} ({$p['descricao_produto']}) cancelada em {$days} dias (<12 meses)";
            $this->createAlertOnce($customer, 'KYT_EARLY_REDEMPTION', $description, 'Alto', 'KYT', 20);
        }
    }

    private function checkHighPremiumLowRiskMemory(Entities $customer, array $policies): void
    {
        foreach ($policies as $p) {
            if ($p['capital'] <= 0 || $p['premium_total'] <= 0) continue;

            $ratio = $p['premium_total'] / max($p['capital'],1);
            if ($ratio < 0.08) continue;

            $description = "Apólice {$p['numero_apolice']} ({$p['descricao_produto']}) – Prémio elevado (ratio: {$ratio})";
            $this->createAlertOnce($customer, 'KYT_HIGH_PREMIUM_LOW_RISK', $description, 'Alto', 'KYT', 25);
        }
    }

    private function checkMultipleShortPoliciesMemory(Entities $customer, array $policies): void
    {
        $short = array_filter($policies, fn($p) => !empty($p['data_inicio']) && !empty($p['data_fim'])
            && ($days = Carbon::parse($p['data_inicio'])->diffInDays(Carbon::parse($p['data_fim']))) >= 90
            && $days <= 180
        );
        if (count($short) < 2) return;

        $apolices = implode(', ', array_map(fn($p) => $p['numero_apolice'].' ('.$p['descricao_produto'].')', $short));
        $description = count($short) . " apólices de curta duração: {$apolices}";
        $this->createAlertOnce($customer, 'KYT_MULTIPLE_SHORT_POLICIES', $description, 'Médio', 'KYT', 15);
    }

    private function checkPolicyChurningMemory(Entities $customer, array $policies): void
    {
        $terminated = array_filter($policies, fn($p) => in_array($p['estado_apolice'], ['terminated','cancelled']));
        if (count($terminated) < 2) return;

        $apolices = implode(', ', array_map(fn($p) => $p['numero_apolice'].' ('.$p['descricao_produto'].')', $terminated));
        $description = count($terminated) . " cancelamentos detectados: {$apolices}";
        $this->createAlertOnce($customer, 'KYT_POLICY_CHURNING', $description, 'Médio', 'KYT', 20);
    }

    private function checkRapidPolicyReplacementMemory(Entities $customer, array $policies): void
    {
        usort($policies, fn($a,$b) => strtotime($a['data_inicio']) - strtotime($b['data_inicio']));

        for ($i = 1; $i < count($policies); $i++) {
            $prev = $policies[$i-1];
            $curr = $policies[$i];
            if (($prev['estado_apolice'] ?? '') !== 'terminated') continue;

            $gap = Carbon::parse($curr['data_inicio'])->diffInDays(Carbon::parse($prev['data_fim']));
            if ($gap > 7) continue;

            $description = "{$prev['numero_apolice']} ({$prev['descricao_produto']}) → {$curr['numero_apolice']} ({$curr['descricao_produto']}) substituição rápida <7 dias";
            $this->createAlertOnce($customer, 'KYT_RAPID_POLICY_REPLACEMENT', $description, 'Médio', 'KYT', 15);
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
                'description' => trim($description),
                'level' => $severity,
                'list' => $list,
                'name' => $customer->social_denomination,
                'score' => $score,
            ]
        );

        try {
            if ($alert->wasRecentlyCreated) {
                $host = config('app.url');
                SendGrupoAlertEmailJob::dispatch($alert->id, $host)->onQueue('high');
                Log::warning("🚨 ALERTA {$type} | Cliente {$customer->customer_number}");
            }
        } catch (\Throwable $th) {
            Log::error("Erro ao enviar alerta {$type}: " . $th->getMessage());
        }
    }
}