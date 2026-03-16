<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomerKYTService
{
    /**
     * Executa todas as regras KYT usando dados em memória
     *
     * @param Entities $customer
     * @param array $policies Array de apólices em memória
     */
    public function runAllChecksMemory(Entities $customer, array $policies): void
    {
        // Ordem importa: simples → complexas
        $this->checkRapidPolicyReplacementMemory($customer, $policies);
        $this->checkEarlyRedemptionMemory($customer, $policies);
        $this->checkHighPremiumLowRiskMemory($customer, $policies);
        $this->checkHighCapitalIncreaseMemory($customer, $policies);
        $this->checkMultipleShortPoliciesMemory($customer, $policies);
        $this->checkPolicyChurningMemory($customer, $policies);
    }

    /* =========================
       REGRAS KYT EM MEMÓRIA
    ========================== */

    private function checkHighCapitalIncreaseMemory(Entities $customer, array $policies): void
    {
        $policies = array_filter($policies, fn($p) => isset($p['data_inicio']));
        usort($policies, fn($a, $b) => strtotime($b['data_inicio']) - strtotime($a['data_inicio']));

        if (count($policies) < 2) return;

        $current  = $policies[0];
        $previous = $policies[1];

        $daysBetween = Carbon::parse($current['data_inicio'])
            ->diffInDays(Carbon::parse($previous['data_inicio']));

        if ($daysBetween > 60 || ($previous['capital'] ?? 0) <= 0) return;

        $increaseRate = (($current['capital'] ?? 0) - ($previous['capital'] ?? 0)) / max($previous['capital'],1);

        if ($increaseRate < 0.40) return;

        $premiumIncreaseRate = 0;
        if (($previous['premium_total'] ?? 0) > 0) {
            $premiumIncreaseRate = (($current['premium_total'] ?? 0) - ($previous['premium_total'] ?? 0))
                / $previous['premium_total'];
        }

        $disproportionateIncrease = $premiumIncreaseRate < ($increaseRate * 0.6);
        $noEconomicReturn = (($current['interest'] ?? 0) <= 0);

        $highRiskProducts = ['Vida', 'Poupança', 'Unit Linked', 'Capitalização','VIDA INDIVIDUAL'];
        if (!in_array($current['descricao_produto'] ?? '', $highRiskProducts)) return;

        if (!$disproportionateIncrease && !$noEconomicReturn) return;

        $description = sprintf(
            "Aumento significativo de capital (%0.2f%%) em %d dias. Apólice anterior %s (%s Kz) → Apólice atual %s (%s Kz). O prémio não acompanhou proporcionalmente o capital e/ou não há retorno económico identificado.",
            $increaseRate * 100,
            $daysBetween,
            $previous['numero_apolice'],
            number_format($previous['capital'] ?? 0, 2, ',', '.'),
            $current['numero_apolice'],
            number_format($current['capital'] ?? 0, 2, ',', '.')
        );

        $this->createAlertOnce(
            $customer->customer_number,
            'Detetado aumento anormal de capital',
            $description,
            'Alto',
            'Aumento Irregular',
            30
        );
    }

    private function checkEarlyRedemptionMemory(Entities $customer, array $policies): void
    {
        $terminatedStatuses = ['terminated', 'cancelled', 'closed', 'rescinded'];
        $products = ['Vida', 'Poupança', 'Unit Linked', 'Capitalização'];

        foreach ($policies as $policy) {
            if (!in_array($policy['estado_apolice'] ?? '', $terminatedStatuses)) continue;
            if (empty($policy['data_inicio']) || empty($policy['data_fim'])) continue;
            if (!in_array($policy['descricao_produto'] ?? '', $products)) continue;

            $daysActive = Carbon::parse($policy['data_inicio'])
                ->diffInDays(Carbon::parse($policy['data_fim']));

            if ($daysActive >= 365) continue;

            $lossAccepted = (($policy['encargos'] ?? 0) > 0) || (($policy['interest'] ?? 0) <= 0 && $daysActive < 180);

            if (!$lossAccepted) continue;

            $description = sprintf(
                "Cancelamento/resgate após %d dias da apólice %s (%s). Capital %s Kz com aceitação de perdas financeiras.",
                $daysActive,
                $policy['numero_apolice'],
                $policy['descricao_produto'],
                number_format($policy['capital'] ?? 0, 2, ',', '.')
            );

            $this->createAlertOnce(
                $customer->customer_number,
                'Resgate antecipado detectado',
                $description,
                'Alto',
                'Resgate antecipado detectado',
                20
            );
        }
    }

    private function checkHighPremiumLowRiskMemory(Entities $customer, array $policies): void
    {
        $lowRiskProducts = ['Vida Term', 'Vida Simples', 'Funeral', 'Acidentes Pessoais'];

        foreach ($policies as $policy) {
            if (($policy['capital'] ?? 0) <= 0 || ($policy['premium_total'] ?? 0) <= 0) continue;
            if (!in_array($policy['descricao_produto'] ?? '', $lowRiskProducts)) continue;

            $ratio = ($policy['premium_total'] ?? 0) / max(($policy['capital'] ?? 1),1);
            if ($ratio < 0.08) continue;
            if (($policy['interest'] ?? 0) > 0) continue;

            $description = sprintf(
                "Prémio elevado (%0.2f%% do capital) em produto de baixo risco. Apólice %s (%s). Capital %s Kz, prémio %s Kz.",
                $ratio * 100,
                $policy['numero_apolice'],
                $policy['descricao_produto'],
                number_format($policy['capital'] ?? 0, 2, ',', '.'),
                number_format($policy['premium_total'] ?? 0, 2, ',', '.')
            );

            $this->createAlertOnce(
                $customer->customer_number,
                'Detetado prémio elevado com nível de risco baixo',
                $description,
                'Alto',
                'Prémio elevado para risco baixo',
                25
            );
        }
    }

    private function checkMultipleShortPoliciesMemory(Entities $customer, array $policies): void
    {
        $fromDate = now()->subMonths(12);

        $filtered = array_filter($policies, function ($policy) use ($fromDate) {
            if (empty($policy['data_inicio']) || empty($policy['data_fim'])) return false;
            $start = Carbon::parse($policy['data_inicio']);
            $end = Carbon::parse($policy['data_fim']);
            $days = $start->diffInDays($end);
            return $start >= $fromDate && $days >= 90 && $days <= 180;
        });

        if (count($filtered) < 2) return;

        $totalCapital = array_sum(array_column($filtered, 'capital'));
        if ($totalCapital < 150000) return;

        $description = sprintf(
            "%d apólices de curta duração (3–6 meses) nos últimos 12 meses. Capital agregado %s Kz.",
            count($filtered),
            number_format($totalCapital, 2, ',', '.')
        );

        $this->createAlertOnce(
            $customer->customer_number,
            'Substituição ou cancelamento repetido',
            $description,
            'Médio',
            'Churn de apólice detectado',
            20
        );
    }

    private function checkPolicyChurningMemory(Entities $customer, array $policies): void
    {
        $terminated = array_filter($policies, fn($p) => in_array($p['estado_apolice'] ?? '', ['terminated','cancelled']) && !empty($p['data_fim']));
        usort($terminated, fn($a,$b) => strtotime($a['data_fim']) - strtotime($b['data_fim']));

        $cycles = [];

        foreach ($terminated as $policy) {
            $replacement = null;
            foreach ($policies as $p) {
                if (empty($p['data_inicio'])) continue;
                $start = Carbon::parse($p['data_inicio']);
                $endPrev = Carbon::parse($policy['data_fim']);
                if ($start->between($endPrev, $endPrev->copy()->addDays(60)) &&
                    ($p['descricao_produto'] ?? '') === ($policy['descricao_produto'] ?? '') &&
                    abs(($p['capital'] ?? 0) - ($policy['capital'] ?? 0)) / max(($policy['capital'] ?? 1),1) <= 0.3
                ) {
                    $replacement = $p;
                    break;
                }
            }

            if ($replacement) {
                $cycles[] = $policy['numero_apolice'];
            }
        }

        if (count($cycles) < 2) return;

        $description = sprintf(
            "%d ciclos de cancelamento e nova subscrição em curto espaço de tempo. Apólices envolvidas: %s.",
            count($cycles),
            implode(', ', $cycles)
        );

        $this->createAlertOnce(
            $customer->customer_number,
            'Churn de apólice',
            $description,
            'Médio',
            'Rotatividade de Apólices',
            20
        );
    }

    private function checkRapidPolicyReplacementMemory(Entities $customer, array $policies): void
    {
        $policies = array_filter($policies, fn($p) => !empty($p['data_inicio']));
        usort($policies, fn($a,$b) => strtotime($a['data_inicio']) - strtotime($b['data_inicio']));

        for ($i = 1; $i < count($policies); $i++) {
            $prev = $policies[$i-1];
            $curr = $policies[$i];

            if (($prev['estado_apolice'] ?? '') !== 'terminated' || empty($prev['data_fim'])) continue;

            $gap = Carbon::parse($curr['data_inicio'])->diffInDays(Carbon::parse($prev['data_fim']));

            if ($gap > 7 || ($prev['descricao_produto'] ?? '') !== ($curr['descricao_produto'] ?? '')) continue;

            $description = sprintf(
                "Apólice %s substituída por %s em %d dias.",
                $prev['numero_apolice'],
                $curr['numero_apolice'],
                $gap
            );

            $this->createAlertOnce(
                $customer->customer_number,
                'Detetada substituição rápida de apólice',
                $description,
                'Médio',
                'Substituição Rápida de Apólice',
                15
            );

            break;
        }
    }

    /* =========================
       ALERTA ÚNICO
    ========================== */

    private function createAlertOnce(
        int $entityId,
        string $type,
        string $description,
        string $severity,
        string $list,
        int $score
    ): void {
        $entitie = Entities::find($entityId);
        $alert = Alert::updateOrCreate(
            [
                'contract_number' => $entityId,
                'type' => $type,
            ],
            [
                'category' => 'KYT',
                'description' => trim($description),
                'level' => $severity,
                'list' => $list,
                'name' => $entitie ? $entitie->social_denomination : 'Desconhecido',
                'score' => $score,
            ]
        );

        if ($alert->wasRecentlyCreated) {
            SendGrupoAlertEmailJob::dispatch($alert->id, config('app.url'))
                ->onQueue('high');

            Log::warning("🚨 NOVO ALERTA KYT | {$type} | Cliente {$entityId}");
        }
    }
}