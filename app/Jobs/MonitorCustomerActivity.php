<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Models\Transation\Policies;
use App\Models\Alert\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitorCustomerActivity implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        Log::info('🔍 KYT Monitor iniciado');

        $customers = Entities::all();

        foreach ($customers as $customer) {
            $this->checkHighCapitalIncrease($customer);
            $this->checkEarlyRedemption($customer);
            $this->checkHighPremiumLowRisk($customer);
            $this->checkMultipleShortPolicies($customer);
            $this->checkPolicyChurning($customer);
            $this->checkRapidPolicyReplacement($customer);
        }

        Log::info('✅ KYT Monitor finalizado');
    }




private function checkHighCapitalIncrease(Entities $customer): void
{
    $policies = Policies::where('entity_id', $customer->id)
        ->orderBy('start_date', 'desc')
        ->take(2)
        ->get();

    // Histórico mínimo
    if ($policies->count() < 2) {
        return;
    }

    $current  = $policies[0];
    $previous = $policies[1];

    // Datas válidas
    if (!$current->start_date || !$previous->start_date) {
        return;
    }

    // Apólices sucessivas (≤ 30 dias)
    $daysBetween = Carbon::parse($current->start_date)
        ->diffInDays(Carbon::parse($previous->start_date));

    if ($daysBetween > 30) {
        return;
    }

    // Evita divisão por zero
    if ($previous->capital <= 0) {
        return;
    }

    // Percentagem de aumento de capital
    $increaseRate = ($current->capital - $previous->capital) / $previous->capital;

    // Threshold regulatório (>= 50%)
    if ($increaseRate < 0.5) {
        return;
    }

    // Inferência: aumento de capital sem aumento proporcional do prémio
    $premiumIncreaseRate = 0;
    if ($previous->premium_total > 0) {
        $premiumIncreaseRate = ($current->premium_total - $previous->premium_total)
            / $previous->premium_total;
    }

    $disproportionateIncrease = $premiumIncreaseRate < ($increaseRate * 0.5);

    // Inferência: ausência de rendimento financeiro
    $noEconomicReturn = ($current->interest == 0);

    // Apenas produtos de acumulação / risco KYT
    $highRiskProducts = ['Vida', 'Poupança', 'Unit Linked', 'Capitalização'];
    if (!$current->product || !in_array($current->product, $highRiskProducts)) {
        return;
    }

    // Se não houver sinais de falta de racional económico, não dispara
    if (!$disproportionateIncrease && !$noEconomicReturn) {
        return;
    }

    // Descrição auditável (ARSEG / GAFI)
    $description = sprintf(
        "KYT_HIGH_CAPITAL_INCREASE: Aumento abrupto e potencialmente injustificado do capital seguro. " .
        "Capital anterior: %s Kz (Apólice %s); Capital atual: %s Kz (Apólice %s). " .
        "Aumento de %.2f%% em %d dias. O incremento do prémio não acompanha proporcionalmente o aumento do capital " .
        "e/ou não há geração de rendimento financeiro identificável. " .
        "Cenário alinhado ao Guia de Operações Suspeitas da ARSEG e às orientações do GAFI " .
        "sobre produtos com elevada capacidade de acumulação de valores.",
        number_format($previous->capital, 2, ',', '.'),
        $previous->contract_number,
        number_format($current->capital, 2, ',', '.'),
        $current->contract_number,
        $increaseRate * 100,
        $daysBetween
    );

    // Alerta KYT
    $this->createAlertOnce(
        $customer->id,
        'KYT_HIGH_CAPITAL_INCREASE',
        $description,
        'Alto',
        'checkHighCapitalIncrease',
        30 // score KYT correto
    );

    // Opcional: EDD (pode ser ligado depois)
    // $this->markCustomerForEDD($customer->id);
}





    private function checkEarlyRedemption(Entities $customer): void
    {
          $policies = Policies::where('entity_id', $customer->id)
        ->whereIn('status', ['terminated', 'cancelled'])
        ->whereNotNull('start_date')
        ->whereNotNull('end_date')
        ->get();

    foreach ($policies as $policy) {

        // Duração da apólice
        $daysActive = Carbon::parse($policy->start_date)
            ->diffInDays(Carbon::parse($policy->end_date));

        // Apenas resgates antes de 12 meses
        if ($daysActive >= 365) {
            continue;
        }

        // Apenas produtos tipicamente resgatáveis
        $rescuableProducts = ['Vida', 'Poupança', 'Unit Linked', 'Capitalização'];
        if (!$policy->product || !in_array($policy->product, $rescuableProducts)) {
            continue;
        }

        // Inferência de penalização financeira
        $hasFinancialLoss = (
            ($policy->charges > 0) ||
            ($policy->interest == 0 && $daysActive < 180)
        );

        if (!$hasFinancialLoss) {
            continue;
        }

        // Descrição auditável ARSEG / GAFI
        $description = sprintf(
            "KYT_EARLY_REDEMPTION: Resgate ou cancelamento antecipado de apólice antes de 12 meses. " .
            "Apólice %s (%s) rescindida após %d dias (%s a %s). Capital: %s Kz. " .
            "Foram identificadas perdas financeiras (encargos/ausência de rendimentos), " .
            "indicando aceitação de penalização para obtenção rápida de liquidez. " .
            "Tipologia reconhecida pela ARSEG e classificada pelo GAFI como red flag " .
            "de integração de fundos ilícitos.",
            $policy->contract_number,
            $policy->product,
            $daysActive,
            $policy->start_date,
            $policy->end_date,
            number_format($policy->capital, 2, ',', '.')
        );

        // Alerta KYT
        $this->createAlertOnce(
            $customer->id,
            'KYT_EARLY_REDEMPTION',
            $description,
            'Alto',
            'detectEarlyRedemptionKYT',
            20 // score correto
        );
    } }

    private function checkHighPremiumLowRisk(Entities $customer): void
{
    $policies = Policies::where('entity_id', $customer->id)
        ->where('status', 'active')
        ->get();

    foreach ($policies as $policy) {

        $capital       = (float) $policy->capital;
        $premiumTotal  = (float) $policy->premium_total;

        if ($capital <= 0 || $premiumTotal <= 0) {
            continue;
        }

        // Apenas produtos de baixo risco
        $lowRiskProducts = ['Vida Term', 'Vida Simples', 'Funeral', 'Acidentes Pessoais'];
        if (!$policy->product || !in_array($policy->product, $lowRiskProducts)) {
            continue;
        }

        // Rácio prémio / capital
        $premiumToCapitalRatio = $premiumTotal / $capital;

        // Threshold AML: prémio ≥ 20% do capital segurado
        if ($premiumToCapitalRatio < 0.20) {
            continue;
        }

        // Inferência adicional: ausência de retorno financeiro
        $noEconomicReturn = ($policy->interest == 0);

        if (!$noEconomicReturn) {
            continue;
        }

        // Descrição auditável (ARSEG / GAFI)
        $description = sprintf(
            "KYT_HIGH_PREMIUM_LOW_RISK: Prémio elevado incompatível com o risco segurado. " .
            "Apólice %s (%s) apresenta capital segurado de %s Kz e prémio total de %s Kz, " .
            "correspondendo a %.2f%% do capital. Produto classificado como baixo risco, " .
            "sem geração de rendimento financeiro identificável. " .
            "Cenário alinhado ao Guia de Operações Suspeitas da ARSEG e às orientações do GAFI " .
            "relativas a prémios desproporcionais como método de colocação de fundos ilícitos.",
            $policy->contract_number,
            $policy->product,
            number_format($capital, 2, ',', '.'),
            number_format($premiumTotal, 2, ',', '.'),
            $premiumToCapitalRatio * 100
        );

        // Alerta KYT
        $this->createAlertOnce(
            $customer->id,
            'KYT_HIGH_PREMIUM_LOW_RISK',
            $description,
            'Alto',
            'checkHighPremiumLowRisk',
            25 // score correto
        );

        // Compliance actions (opcional / recomendável)
        // $this->markCustomerForEDD($customer->id);
    }
}

    private function checkMultipleShortPolicies(Entities $customer): void
{
    // Janela temporal de análise (últimos 12 meses)
    $fromDate = now()->subMonths(12);

    $policies = Policies::where('entity_id', $customer->id)
        ->whereNotNull('start_date')
        ->whereNotNull('end_date')
        ->where('start_date', '>=', $fromDate)
        ->get()
        ->filter(function ($policy) {

            // Duração da apólice
            $days = Carbon::parse($policy->start_date)
                ->diffInDays(Carbon::parse($policy->end_date));

            return $days >= 90 && $days <= 180; // curta duração típica
        });

    // Mínimo de 3 apólices
    if ($policies->count() < 3) {
        return;
    }

    // Soma de capital fragmentado
    $totalCapital = $policies->sum('capital');

    // Threshold mínimo agregado (ajustável)
    if ($totalCapital < 300000) { // exemplo: 300.000 Kz
        return;
    }

    $contracts = $policies->pluck('contract_number')->join(', ');

    $description = sprintf(
        "KYT_MULTIPLE_SHORT_POLICIES: Subscrição sequencial de múltiplas apólices de curta duração. " .
        "Foram identificadas %d apólices com duração entre 3 e 6 meses, subscritas nos últimos 12 meses, " .
        "totalizando capital agregado de %s Kz. Apólices: %s. " .
        "Padrão compatível com fragmentação de contratos para dispersão de valores, " .
        "tipologia reconhecida pelo GAFI e pelo Guia de Operações Suspeitas da ARSEG.",
        $policies->count(),
        number_format($totalCapital, 2, ',', '.'),
        $contracts
    );

    $this->createAlertOnce(
        $customer->id,
        'KYT_MULTIPLE_SHORT_POLICIES',
        $description,
        'Médio',
        'checkMultipleShortPolicies',
        15 // score correto
    );

    // Opcional: EDD se combinado com outros cenários
    // $this->markCustomerForEDD($customer->id);
}

   private function checkPolicyChurning(Entities $customer): void
{
    // Janela de análise
    $fromDate = now()->subYear();

    // Apólices canceladas no período
    $terminatedPolicies = Policies::where('entity_id', $customer->id)
        ->where('status', 'terminated')
        ->whereNotNull('end_date')
        ->where('end_date', '>=', $fromDate)
        ->orderBy('end_date')
        ->get();

    if ($terminatedPolicies->count() < 3) {
        return;
    }

    $churnedPolicies = [];

    foreach ($terminatedPolicies as $terminated) {

        // Procura nova apólice após cancelamento (≤ 60 dias)
        $replacement = Policies::where('entity_id', $customer->id)
            ->whereNotNull('start_date')
            ->where('start_date', '>', $terminated->end_date)
            ->where('start_date', '<=', Carbon::parse($terminated->end_date)->addDays(60))
            ->orderBy('start_date')
            ->first();

        if (!$replacement) {
            continue;
        }

        // Produto semelhante (inferência)
        if ($terminated->product !== $replacement->product) {
            continue;
        }

        // Capital semelhante (±20%)
        if ($terminated->capital > 0) {
            $variation = abs($replacement->capital - $terminated->capital)
                / $terminated->capital;

            if ($variation > 0.2) {
                continue;
            }
        }

        $churnedPolicies[] = $terminated->contract_number;
    }

    // Mínimo de 3 ciclos de churning
    if (count($churnedPolicies) < 3) {
        return;
    }

    $description = sprintf(
        "KYT_POLICY_CHURNING: Cancelamentos frequentes de apólices seguidos de substituição por novas apólices similares. " .
        "Foram identificados %d ciclos de cancelamento e nova subscrição num período inferior a 12 meses. " .
        "Apólices envolvidas: %s. Padrão compatível com churning contratual, permitindo movimento recorrente de fundos, " .
        "conforme tipologias descritas pela ARSEG e red flags do GAFI relativas a ida e volta de valores.",
        count($churnedPolicies),
        implode(', ', $churnedPolicies)
    );

    $this->createAlertOnce(
        $customer->id,
        'KYT_POLICY_CHURNING',
        $description,
        'Médio',
        'checkPolicyChurning',
        20 // score correto
    );

    // Opcional: escalar se combinado com outros cenários
    // $this->markCustomerForEDD($customer->id);
}

private function checkRapidPolicyReplacement(Entities $customer): void
{
    $policies = Policies::where('entity_id', $customer->id)
        ->whereNotNull('start_date')
        ->orderBy('start_date')
        ->get();

    if ($policies->count() < 2) {
        return;
    }

    for ($i = 1; $i < $policies->count(); $i++) {

        $previous = $policies[$i - 1];
        $current  = $policies[$i];

        // Apólice anterior tem de estar terminada
        if ($previous->status !== 'terminated' || !$previous->end_date) {
            continue;
        }

        // Intervalo entre cancelamento e nova subscrição
        $gap = Carbon::parse($current->start_date)
            ->diffInDays(Carbon::parse($previous->end_date));

        if ($gap > 7) {
            continue;
        }

        // Produto semelhante
        if ($previous->product !== $current->product) {
            continue;
        }

        // Capital igual ou superior (movimento progressivo)
        if ($current->capital < $previous->capital) {
            continue;
        }

        $description = sprintf(
            "KYT_RAPID_POLICY_REPLACEMENT: Substituição rápida de apólice detectada. " .
            "Apólice %s foi cancelada e substituída pela apólice %s após %d dias. " .
            "Produto: %s. Capital anterior: %s Kz; Capital atual: %s Kz. " .
            "Padrão compatível com layering acelerado e obfuscação de fluxos, " .
            "conforme tipologias descritas pela ARSEG e red flags do GAFI.",
            $previous->contract_number,
            $current->contract_number,
            $gap,
            $current->product,
            number_format($previous->capital, 2, ',', '.'),
            number_format($current->capital, 2, ',', '.')
        );

        // Garantia extra
        $description = str_replace(["\n", "\r"], ' ', $description);

        $this->createAlertOnce(
            $customer->id,
            'KYT_RAPID_POLICY_REPLACEMENT',
            $description,
            'Médio',
            'checkRapidPolicyReplacement',
            15 // score correto
        );

        break; // um caso já é suficiente
    }
}


   private function createAlertOnce(
    int $entityId,
    string $type,
    string $description,
    string $severity,
    string $list,
    string $score
): void {
    $entity = Entities::find($entityId);

    $description = trim(str_replace(["\n", "\r"], ' ', $description));

    $alert = Alert::updateOrCreate(
        [
            'entity_id' => $entityId,
            'type'      => $type,
        ],
        [
            'category'    => 'KYT',
            'description' => $description,
            'level'       => $severity,
            'list'        => $list,
            'score'       => $score,
            'name'        => $entity->social_denomination ?? 'UNKNOWN',
        ]
    );

    // ✅ SÓ ENVIA EMAIL SE FOI CRIADO AGORA
    if ($alert->wasRecentlyCreated) {
        $host = config('app.url');

        SendGrupoAlertEmailJob::dispatch($alert->id, $host)
            ->onQueue('high');

        Log::warning("🚨 NOVO ALERTA | {$type} | Cliente {$entityId}");
    } else {
        Log::info("ℹ️ Alerta já existente — email não enviado", [
            'alert_id' => $alert->id,
            'entity_id'=> $entityId,
            'type'     => $type,
        ]);
    }
}

}
