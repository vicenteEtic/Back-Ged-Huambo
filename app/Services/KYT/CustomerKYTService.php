<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Transation\Policies;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
class CustomerKYTService
{
    /**
     * Executa todas as regras KYT
     */
    public function runAllChecks(Entities $customer): void
    {
        // Ordem importa: simples → complexas
        $this->checkRapidPolicyReplacement($customer);
        $this->checkEarlyRedemption($customer);
        $this->checkHighPremiumLowRisk($customer);
        $this->checkHighCapitalIncrease($customer);
        $this->checkMultipleShortPolicies($customer);
        $this->checkPolicyChurning($customer);
    }

    /* =========================
       REGRAS KYT
    ========================== */

    /**
     * Aumento abrupto de capital
     */
    private function checkHighCapitalIncrease(Entities $customer): void
    {
        $policies = Policies::where('entity_id', $customer->id)
            ->whereNotNull('start_date')
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get();

        if ($policies->count() < 2) {
            return;
        }

        $current  = $policies[0];
        $previous = $policies[1];

        $daysBetween = Carbon::parse($current->start_date)
            ->diffInDays(Carbon::parse($previous->start_date));

        if ($daysBetween > 60 || $previous->capital <= 0) {
            return;
        }

        $increaseRate = ($current->capital - $previous->capital) / $previous->capital;

        if ($increaseRate < 0.40) { // ↓ 50% → 40%
            return;
        }

        $premiumIncreaseRate = 0;
        if ($previous->premium_total > 0) {
            $premiumIncreaseRate = ($current->premium_total - $previous->premium_total)
                / $previous->premium_total;
        }

        $disproportionateIncrease = $premiumIncreaseRate < ($increaseRate * 0.6);
        $noEconomicReturn = ((float) $current->interest) <= 0;

        $highRiskProducts = ['Vida', 'Poupança', 'Unit Linked', 'Capitalização'];
        if (!in_array($current->product, $highRiskProducts)) {
            return;
        }

        if (!$disproportionateIncrease && !$noEconomicReturn) {
            return;
        }

        $description = sprintf(
            " Aumento significativo de capital (%0.2f%%) em %d dias. " .
            "Apólice anterior %s (%s Kz) → Apólice atual %s (%s Kz). " .
            "O prémio não acompanhou proporcionalmente o capital e/ou não há retorno económico identificado. " .
            "Padrão compatível com acumulação artificial de valores (ARSEG / GAFI).",
            $increaseRate * 100,
            $daysBetween,
            $previous->contract_number,
            number_format($previous->capital, 2, ',', '.'),
            $current->contract_number,
            number_format($current->capital, 2, ',', '.')
        );

        $this->createAlertOnce(
            $customer->id,
            'KYT_HIGH_CAPITAL_INCREASE',
            $description,
            'Alto',
            'Aumento Irregular',
            30
        );
    }

    /**
     * Resgate antecipado
     */
    private function checkEarlyRedemption(Entities $customer): void
    {
        $policies = Policies::where('entity_id', $customer->id)
            ->whereIn('status', ['terminated', 'cancelled', 'closed', 'rescinded'])
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->get();

        foreach ($policies as $policy) {

            $daysActive = Carbon::parse($policy->start_date)
                ->diffInDays(Carbon::parse($policy->end_date));

            if ($daysActive >= 365) {
                continue;
            }

            $products = ['Vida', 'Poupança', 'Unit Linked', 'Capitalização'];
            if (!in_array($policy->product, $products)) {
                continue;
            }

            $lossAccepted = (
                (float) $policy->charges > 0 ||
                ((float) $policy->interest <= 0 && $daysActive < 180)
            );

            if (!$lossAccepted) {
                continue;
            }

            $description = sprintf(
                "Cancelamento/resgate após %d dias da apólice %s (%s). " .
                "Capital %s Kz com aceitação de perdas financeiras. " .
                "Indicador típico de obtenção rápida de liquidez (GAFI).",
                $daysActive,
                $policy->contract_number,
                $policy->product,
                number_format($policy->capital, 2, ',', '.')
            );

            $this->createAlertOnce(
                $customer->id,
                'KYT_EARLY_REDEMPTION',
                $description,
                'Alto',
                'Resgate Antecipado',
                20
            );
        }
    }

    /**
     * Prémio elevado em produto de baixo risco
     */
    private function checkHighPremiumLowRisk(Entities $customer): void
    {
        $policies = Policies::where('entity_id', $customer->id)
            ->where('status', 'active')
            ->get();

        foreach ($policies as $policy) {

            if ($policy->capital <= 0 || $policy->premium_total <= 0) {
                continue;
            }

            $lowRiskProducts = ['Vida Term', 'Vida Simples', 'Funeral', 'Acidentes Pessoais'];
            if (!in_array($policy->product, $lowRiskProducts)) {
                continue;
            }

            $ratio = $policy->premium_total / $policy->capital;

            if ($ratio < 0.08) { // ↓ 20% → 8%
                continue;
            }

            if ((float) $policy->interest > 0) {
                continue;
            }

            $description = sprintf(
                "Prémio elevado (%0.2f%% do capital) em produto de baixo risco. " .
                "Apólice %s (%s). Capital %s Kz, prémio %s Kz. " .
                "Padrão de colocação desproporcional de fundos.",
                $ratio * 100,
                $policy->contract_number,
                $policy->product,
                number_format($policy->capital, 2, ',', '.'),
                number_format($policy->premium_total, 2, ',', '.')
            );

            $this->createAlertOnce(
                $customer->id,
                'KYT_HIGH_PREMIUM_LOW_RISK',
                $description,
                'Alto',
                'Prêmio Alto, Risco Baixo',
                25
            );
        }
    }

    /**
     * Múltiplas apólices curtas
     */
    private function checkMultipleShortPolicies(Entities $customer): void
    {
        $fromDate = now()->subMonths(12);

        $policies = Policies::where('entity_id', $customer->id)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('start_date', '>=', $fromDate)
            ->get()
            ->filter(function ($policy) {
                $days = Carbon::parse($policy->start_date)
                    ->diffInDays(Carbon::parse($policy->end_date));
                return $days >= 90 && $days <= 180;
            });

        if ($policies->count() < 2) {
            return;
        }

        if ($policies->sum('capital') < 150000) {
            return;
        }

        $description = sprintf(
            "%d apólices de curta duração (3–6 meses) nos últimos 12 meses. " .
            "Capital agregado %s Kz. Padrão compatível com fragmentação de valores.",
            $policies->count(),
            number_format($policies->sum('capital'), 2, ',', '.')
        );

        $this->createAlertOnce(
            $customer->id,
            'KYT_MULTIPLE_SHORT_POLICIES',
            $description,
            'Médio',
            'Múltiplas Apólices Curtas',
            15
        );
    }

    /**
     * Churning contratual
     */
    private function checkPolicyChurning(Entities $customer): void
    {
        $terminated = Policies::where('entity_id', $customer->id)
            ->whereIn('status', ['terminated', 'cancelled'])
            ->whereNotNull('end_date')
            ->orderBy('end_date')
            ->get();

        $cycles = [];

        foreach ($terminated as $policy) {

            $replacement = Policies::where('entity_id', $customer->id)
                ->whereNotNull('start_date')
                ->whereBetween(
                    'start_date',
                    [$policy->end_date, Carbon::parse($policy->end_date)->addDays(60)]
                )
                ->first();

            if (
                $replacement &&
                $replacement->product === $policy->product &&
                abs($replacement->capital - $policy->capital) / max($policy->capital, 1) <= 0.3
            ) {
                $cycles[] = $policy->contract_number;
            }
        }

        if (count($cycles) < 2) {
            return;
        }

        $description = sprintf(
            " %d ciclos de cancelamento e nova subscrição em curto espaço de tempo. " .
            "Apólices envolvidas: %s.",
            count($cycles),
            implode(', ', $cycles)
        );

        $this->createAlertOnce(
            $customer->id,
            'KYT_POLICY_CHURNING',
            $description,
            'Médio',
            'Rotatividade de Apólices',
            20
        );
    }

    /**
     * Substituição rápida
     */
    private function checkRapidPolicyReplacement(Entities $customer): void
    {
        $policies = Policies::where('entity_id', $customer->id)
            ->whereNotNull('start_date')
            ->orderBy('start_date')
            ->get();

        for ($i = 1; $i < $policies->count(); $i++) {

            $prev = $policies[$i - 1];
            $curr = $policies[$i];

            if ($prev->status !== 'terminated' || !$prev->end_date) {
                continue;
            }

            $gap = Carbon::parse($curr->start_date)
                ->diffInDays(Carbon::parse($prev->end_date));

            if ($gap > 7 || $prev->product !== $curr->product) {
                continue;
            }

            $description = sprintf(
                "Apólice %s substituída por %s em %d dias.",
                $prev->contract_number,
                $curr->contract_number,
                $gap
            );

            $this->createAlertOnce(
                $customer->id,
                'KYT_RAPID_POLICY_REPLACEMENT',
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
      $entitie=  Entities::find($entityId);
        $alert = Alert::updateOrCreate(
            [
                'entity_id' => $entityId,
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
