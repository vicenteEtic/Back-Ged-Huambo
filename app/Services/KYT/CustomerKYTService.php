<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use App\Models\Entities\RiskAssessment;
use App\Models\Indicator\IndicatorType;
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
        array $receipts = [],
        array $beneficiaries = []
    ): void {

        Log::info("🚀 KYT START", [
            'customer' => $customer->customer_number,
            'policies' => count($policies)
        ]);

        $policies = array_map(fn($p) => $this->toArray($p), $policies);
        $changes = array_map(fn($c) => $this->toArray($c), $changes);
        $refunds = array_map(fn($r) => $this->toArray($r), $refunds);
        $receipts = array_map(fn($r) => $this->toArray($r), $receipts);
        $beneficiaries = array_map(fn($b) => $this->toArray($b), $beneficiaries);


        if (empty($policies)) return;

        //1. KYT_HIGH_CAPITAL_INCREASE
        $this->checkHighCapitalIncrease($customer, $changes);

        //2. KYT_EARLY_REDEMPTION
        $this->checkEarlyRedemption($customer,  $policies, $receipts, $refunds);

        //3. KYT_HIGH_PREMIUM_LOW_RISK
        $this->checkHighPremium($customer, $policies);

        //4. KYT_MULTIPLE_SHORT_POLICIES
        $this->checkMultipleShortPolicies($customer, $policies);

        //5. KYT_POLICY_CHURNING
        $this->checkPolicyChurning($customer, $policies);

        //6. KYT_RAPID_POLICY_REPLACEMENT
        $this->checkRapidReplacement($customer, $policies);

        //7. KYT_THIRD_PARTY_PAYMENTS
        $this->checkThirdPartyPayments($customer, $policies);

        //8. KYT_FREQUENT_BENEFICIARY_CHANGES
        $this->checkFrequentBeneficiaryChanges($customer, $beneficiaries);

        //9. KYT_HIGH_RISK_GEOGRAPHY
        $this->checkHighRiskGeography($customer, $policies,  $receipts, $beneficiaries);

        //10. KYT_OVERPAYMENT_REFUND
        $this->checkOverpaymentRefund($customer, $policies, $receipts, $refunds);

        Log::info("🏁 KYT FINISHED", [
            'customer' => $customer->customer_number
        ]);
    }




    private function checkOverpaymentRefund(
        Entities $customer,
        array $policies,
        array $receipts = [],
        array $refunds = []
    ): void {
        if (empty($receipts) || empty($refunds)) return;

        $alerts = [];

        foreach ($receipts as $receipt) {

            $policyNumber = $receipt['Numero_Apolice'] ?? null;
            if (!$policyNumber) continue;

            // 🔥 valor pago
            $paidAmount = (float) ($receipt['Valor_Pago'] ?? 0);
            if ($paidAmount <= 0) continue;

            // 🔥 buscar apólice
            $policy = collect($policies)->firstWhere('numero_apolice', $policyNumber);
            if (!$policy) continue;

            $expectedPremium = (float) ($policy['premium_total'] ?? 0);
            if ($expectedPremium <= 0) continue;

            // 🔥 REGRA PRINCIPAL → SOBRE PAGAMENTO >=150%
            $ratio = $paidAmount / $expectedPremium;

            if ($ratio < 1.5) continue;

            // 🔥 PAGADOR ORIGINAL
            $originalPayer = $receipt['Nome_Pagador'] ?? null;

            // 🔥 DATA PAGAMENTO
            $paymentDate = $this->safeDate($receipt['Data_Pagamento'] ?? null);
            if (!$paymentDate) continue;

            foreach ($refunds as $refund) {

                $refundPolicy = $refund['Numero_Apolice'] ?? null;

                if ($refundPolicy !== $policyNumber) continue;

                $refundAmount = (float) ($refund['Valor_Estorno'] ?? 0);
                if ($refundAmount <= 0) continue;

                $refundDate = $this->safeDate($refund['Data_Estorno'] ?? null);
                if (!$refundDate) continue;

                // 🔥 intervalo curto (<=30 dias)
                $days = $paymentDate->diffInDays($refundDate);

                if ($days > 30) continue;

                // 🔥 DESTINO DO REEMBOLSO
                $refundReceiver = $refund['Nome_Beneficiario'] ?? null;

                // 🔥 TERCEIRO (CRÍTICO AML)
                $isThirdParty = $refundReceiver && $originalPayer &&
                    trim(strtolower($refundReceiver)) !== trim(strtolower($originalPayer));

                if (!$isThirdParty) continue;

                // 🔥 evitar duplicados
                $key = $policyNumber . '_' . $paymentDate->format('Ymd');
                if (in_array($key, $alerts)) continue;

                $alerts[] = $key;

                /* =========================
                   DESCRIÇÃO (NÍVEL AUDITORIA)
                ========================== */

                $description =
                    "RELATÓRIO KYT - SOBREPAGAMENTO COM REEMBOLSO A TERCEIROS
    
    Cliente: {$customer->customer_number}
    
    Resumo do comportamento:
    - Apólice: {$policyNumber}
    - Prémio esperado: " . $this->formatMoney($expectedPremium) . "
    - Valor pago: " . $this->formatMoney($paidAmount) . " (" . round($ratio * 100, 2) . "%)
    - Valor reembolsado: " . $this->formatMoney($refundAmount) . "
    - Intervalo pagamento → reembolso: {$days} dias
    
    Detalhes do fluxo financeiro:
    - Pagador original: {$originalPayer}
    - Beneficiário do reembolso: {$refundReceiver}
    
    Interpretação AML:
    Foi identificado um sobrepagamento significativo do prémio, seguido de pedido de reembolso
    em curto intervalo para uma entidade diferente do pagador inicial.
    
    Este padrão é altamente consistente com:
    - Injecção de fundos ilícitos através de sobrepagamento
    - Extração de fundos com aparência legítima via reembolso
    - Uso de terceiros para ocultação de origem (layering)
    
    ";

                /* =========================
                   SCORE AML
                ========================== */

                $score = 20;

                if ($ratio >= 2) $score += 5;
                if ($days <= 7) $score += 5;

                $this->createAlert(
                    $customer,
                    'Sobrepagamento de prémios seguido de pedido de reembolso para terceiros',
                    $description,
                    'Alto',
                    $score
                );
            }
        }
    }

    private function checkThirdPartyPayments(Entities $customer, array $policies): void
    {
        foreach ($policies as $policy) {

            // 🔹 Ignora apólices sem prémio ou capital
            if (!$policy['premium_total'] || !$policy['capital']) continue;

            // 🔹 Simulação de pagador; assumimos que $policy['payer'] existe:
            // ['name' => string, 'relation' => string|null, 'origin' => string|null]
            $payer = $policy['payer'] ?? null;

            if (!$payer) continue; // sem informação do pagador, ignora

            // 🔹 Pagador não é o próprio segurado
            $isThirdParty = ($payer['relation'] ?? 'self') !== 'self';

            // 🔹 Montante relevante (exemplo > 100.000 Kz)
            $isHighAmount = $policy['premium_total'] >= 100000;

            // 🔹 Somente apólices iniciais ou renovações recentes (últimos 12 meses)
            $isRecentPolicy = $policy['data_inicio'] && Carbon::parse($policy['data_inicio'])->gte(now()->subYear());

            if ($isThirdParty && $isHighAmount && $isRecentPolicy) {

                $description = sprintf(
                    "Apólice: %s | Prémio: %s | Pagador: %s (%s) | Origem fundos: %s | Segurado: %s",
                    $policy['numero_apolice'],
                    $this->formatMoney($policy['premium_total']),
                    $payer['name'] ?? 'Desconhecido',
                    $payer['relation'] ?? 'Desconhecida',
                    $payer['origin'] ?? 'Desconhecida',
                    $customer->social_denomination
                );

                $this->createAlert(
                    $customer,
                    'Pagamentos de prémios por terceiros sem relação clara com o segurados',
                    $description,
                    'Alto',         // nível de risco
                    25

                );
            }
        }
    }

    private function checkRapidReplacement(Entities $customer, array $policies): void
    {
        usort(
            $policies,
            fn($a, $b) =>
            strtotime($a['data_inicio'] ?? '1970') <=> strtotime($b['data_inicio'] ?? '1970')
        );

        $chains = [];
        $current = [];

        for ($i = 1; $i < count($policies); $i++) {

            $prev = $policies[$i - 1];
            $curr = $policies[$i];

            $cancelDate = $this->safeDate($prev['data_anulacao'] ?? $prev['data_fim'] ?? null);
            $currStart  = $this->safeDate($curr['data_inicio'] ?? null);
            $startPrev  = $this->safeDate($prev['data_inicio'] ?? null);

            if (!$cancelDate || !$currStart || !$startPrev) continue;

            $duration = $startPrev->diffInDays($cancelDate);

            if ($duration > 30) continue;

            $gap = $cancelDate->diffInDays($currStart);

            if ($gap <= 7) {

                if (empty($current) || end($current)['numero_apolice'] !== $prev['numero_apolice']) {
                    $current[] = $prev;
                }

                if (end($current)['numero_apolice'] !== $curr['numero_apolice']) {
                    $current[] = $curr;
                }
            } else {
                if (count($current) >= 3) {
                    $chains[] = $current;
                }
                $current = [];
            }
        }

        if (count($current) >= 3) {
            $chains[] = $current;
        }

        if (empty($chains)) return;

        usort($chains, fn($a, $b) => count($b) <=> count($a));
        $chain = $chains[0] ?? [];

        if (empty($chain)) return;

        // 🔥 últimos 12 meses
        $chain = array_values(array_filter($chain, function ($p) {
            return isset($p['data_inicio']) &&
                Carbon::parse($p['data_inicio'])->gte(now()->subYear());
        }));

        if (count($chain) < 3) return;

        // 🔥 LIMITAR A 20 (AQUI)
        $chain = array_slice($chain, -20);

        $pairs = [];
        $timeline = [];
        $early = 0;

        for ($i = 1; $i < count($chain); $i++) {

            $prev = $chain[$i - 1];
            $curr = $chain[$i];

            $cancelDate = $this->safeDate($prev['data_anulacao'] ?? $prev['data_fim']);
            $currStart  = $this->safeDate($curr['data_inicio']);

            if (!$cancelDate || !$currStart) continue;

            $gap = $cancelDate->diffInDays($currStart);

            $pair = $prev['numero_apolice'] . " → " . $curr['numero_apolice'];

            if (!in_array($pair, $pairs)) {
                $pairs[] = $pair;
            }

            $timeline[] = sprintf(
                "%s (%s → %s = %d dias)",
                $prev['numero_apolice'],
                $cancelDate->format('Y-m-d'),
                $currStart->format('Y-m-d'),
                $gap
            );

            if ($gap <= 7) $early++;
        }

        if (empty($pairs)) return;

        $description =
            "
    
    Cliente: {$customer->customer_number}
    
    Resumo:
    - Eventos analisados (máx 20): " . count($pairs) . "
    - Substituições ≤ 7 dias: {$early}
    
    Cadeia:
    " . implode(', ', $pairs) . "
    
    Timeline:
    " . implode("\n", $timeline) . "
    
    Interpretação AML:
    Padrão de cancelamento e re-substituição em curto prazo (≤7 dias),
    indicando possível layering e ocultação de fluxos financeiros.
    ";

        $score = 15;

        if ($early >= 2) $score += 5;
        if (count($chain) >= 5) $score += 5;

        $this->createAlert(
            $customer,
            'Substituição rápida de apólices',
            $description,
            'Alto',
            $score
        );
    }
    private function checkPolicyChurning(Entities $customer, array $policies): void
    {
        $terminated = array_filter($policies, function ($p) {

            $estado = strtolower(trim($p['estado_apolice'] ?? ''));

            if (!in_array($estado, ['cancelled', 'terminated'])) {
                return false;
            }

            $dataFim = $this->safeDate($p['data_fim'] ?? null);
            if (!$dataFim) return false;

            return $dataFim->gte(now()->subYear());
        });

        if (count($terminated) < 2) return;

        usort($terminated, function ($a, $b) {
            return strtotime($a['data_fim'] ?? '1970-01-01')
                <=> strtotime($b['data_fim'] ?? '1970-01-01');
        });

        $clusters = 0;

        for ($i = 1; $i < count($terminated); $i++) {

            $gap = $this->safeDays(
                $terminated[$i - 1]['data_fim'] ?? null,
                $terminated[$i]['data_fim'] ?? null
            );

            if ($gap !== null && (int)$gap <= 60) {
                $clusters++;
            }
        }

        if ($clusters === 0 && count($terminated) < 5) return;

        $latest = array_slice($terminated, -20);

        $apolices = array_column($latest, 'numero_apolice');

        $description =
            "
    
    Cliente: {$customer->customer_number}
    
    Análise:
    - Cancelamentos (12 meses): " . count($terminated) . "
    - Clusters (≤60 dias): {$clusters}
    
    Apólices:
    " . implode(', ', $apolices) . "
    
    AML INTERPRETAÇÃO:
    Padrão de cancelamentos repetidos em curto espaço de tempo,
    indicando possível:
    - reestruturação artificial de contratos
    - tentativa de ocultação de exposição financeira
    - comportamento compatível com layering";

        $this->createAlert(
            $customer,
            'Cancelamentos frequentes de Apólices num curto Período',
            $description,
            'Médio',
            20
        );
    }

    private function checkMultipleShortPolicies(Entities $customer, array $policies): void
    {
        Log::info('🚀 KYT BULK POLICY DETECTION START', [
            'customer' => $customer->customer_number,
            'total_policies' => count($policies)
        ]);

        $valid = [];

        foreach ($policies as $p) {

            $start = $this->safeDate($p['data_inicio'] ?? null);
            if (!$start) continue;

            // 🔥 últimos 6 meses
            if ($start->lt(now()->subMonths(6))) continue;

            $valid[] = [
                'numero_apolice' => $p['numero_apolice'] ?? null,
                'start' => $start,
                'premium' => (float) ($p['premium_total'] ?? 0)
            ];
        }

        Log::info('📦 RECENT POLICIES', ['count' => count($valid)]);

        if (count($valid) < 3) {
            Log::warning('⛔ EXIT: NOT ENOUGH RECENT POLICIES');
            return;
        }

        usort($valid, fn($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);

        $first = $valid[0]['start'];
        $last  = end($valid)['start'];

        $periodDays = $first->diffInDays($last);

        Log::info('📅 TIME WINDOW', [
            'days' => $periodDays
        ]);

        // 🔥 criação concentrada (30 dias)
        if ($periodDays > 5) {
            Log::warning('⛔ EXIT: NOT CONCENTRATED');
            return;
        }

        $totalPremium = array_sum(array_column($valid, 'premium'));

        $apolices = array_column($valid, 'numero_apolice');

        Log::warning('🚨 BULK POLICY ALERT', [
            'count' => count($valid),
            'totalPremium' => $totalPremium
        ]);

        $description = "


Cliente: {$customer->customer_number}

Resumo:
- Nº Apólices: " . count($valid) . "
- Apólices: " . implode(', ', $apolices) . "
- Período: {$first->format('Y-m-d')} → {$last->format('Y-m-d')} ({$periodDays} dias)
- Prémio total: " . $this->formatMoney($totalPremium) . "

AML:
Criação massiva de apólices em curto período temporal.
Padrão compatível com:
- Smurfing
- Estruturação de valores
- Teste de sistema para movimentação futura
";

        $score = 25;

        if (count($valid) >= 5) $score += 10;
        if ($totalPremium >= 500000) $score += 10;

        $this->createAlert(
            $customer,
            'Subscrição de múltiplas apólices de curta duração',
            $description,
            'Alto',
            $score
        );
    }
    private function checkHighPremium(Entities $customer, array $policies): void
    {
        // 🔹 Agrupa por produto
        $grouped = [];

        foreach ($policies as $p) {
            $produto = $p['descricao_produto'] ?? 'OUTROS';
            $grouped[$produto][] = $p;
        }

        foreach ($grouped as $produto => $group) {

            // 🔹 filtra válidas
            $valid = array_filter($group, function ($p) {
                return $p['capital'] > 0 && $p['premium_total'] > 0;
            });

            if (count($valid) < 1) continue;

            // 🔹 ordena por data (mais recentes primeiro)
            usort(
                $valid,
                fn($a, $b) =>
                strtotime($b['data_inicio'] ?? '1970') - strtotime($a['data_inicio'] ?? '1970')
            );

            // 🔥 últimas 20 analisadas
            $latest = array_slice($valid, 0, 20);

            // 🔹 cálculo com TODAS (regra KYT correta)
            $totalCapital = array_sum(array_column($valid, 'capital'));
            $totalPremium = array_sum(array_column($valid, 'premium_total'));

            if ($totalCapital <= 0 || $totalPremium <= 0) continue;

            $ratio = $totalPremium / $totalCapital;

            if ($ratio >= 0.08) {

                // 🔹 apenas apólices analisadas (últimas 20)
                $apolices = array_column($latest, 'numero_apolice');

                // 🔹 período analisado (melhora auditoria)
                $firstDate = $latest[0]['data_inicio'] ?? null;
                $lastDate  = end($latest)['data_inicio'] ?? null;

                $description = sprintf(
                    "Produto: %s | Últimas 20 apólices: %s | Período: %s → %s | Capital total: %s | Prêmio total: %s | Ratio: %.2f%%",
                    $produto,
                    implode(', ', $apolices),
                    $firstDate,
                    $lastDate,
                    $this->formatMoney($totalCapital),
                    $this->formatMoney($totalPremium),
                    $ratio * 100
                );

                $this->createAlert(
                    $customer,
                    "Prémio elevado incompatível com o risco segurado",
                    $description,
                    'Alto',
                    25
                );
            }
        }
    }
    private function checkHighCapitalIncrease(Entities $customer, array $changes): void
    {
        Log::info('🚀 KYT CAPITAL CHANGE START', [
            'customer' => $customer->customer_number,
            'count' => count($changes)
        ]);

        foreach ($changes as $change) {

            $change = (array) $change;

            $tipo = strtoupper(trim($change['tipo_alteracao'] ?? ''));

            $old = (float) ($change['valor_anterior'] ?? 0);
            $new = (float) ($change['novo_valor'] ?? 0);

            if ($old <= 0 || $new <= 0) continue;

            $increaseRate = ($new - $old) / $old;
            $increaseAbs = abs($new - $old);

            // 🔥 REGRAS AML MAIS INTELIGENTES
            $isRelevantType =
                str_contains($tipo, 'ALTER') ||
                str_contains($tipo, 'ACTA') ||
                str_contains($tipo, 'RENOVAÇÃO');

            if (!$isRelevantType) continue;

            // 🔥 NOVA LÓGICA: valor alto COMPENSA percentagem baixa
            $isHighValueMove = $increaseAbs >= 1000000; // 1M+

            $isModerateIncrease = $increaseRate >= 0.10; // 10%

            if (!$isHighValueMove && !$isModerateIncrease) {
                continue;
            }

            $motivo = strtolower(trim($change['motivo_alteracao'] ?? 'não informado'));

            $motivoValido = in_array($motivo, [
                'herança',
                'mudança de emprego',
                'promoção',
                'renovação automática'
            ]);

            // 🔥 SCORE AML INTELIGENTE
            $score = 15;

            if ($increaseRate >= 0.10) $score += 10;
            if ($increaseRate >= 0.20) $score += 10;
            if ($increaseAbs >= 5000000) $score += 10;
            if (!$motivoValido) $score += 10;

            $riskLevel = match (true) {
                $score >= 40 => 'Crítico',
                $score >= 30 => 'Alto',
                $score >= 20 => 'Médio',
                default => 'Baixo'
            };

            // 🔥 DESCRIÇÃO MELHORADA (nível auditoria bancária)
            $description = "
   
    
    IDENTIFICAÇÃO
    - Cliente: {$customer->customer_number}
    - Apólice: " . ($change['numero_apolice'] ?? 'N/A') . "
    - Tipo de alteração: {$tipo}
    
    MOVIMENTO FINANCEIRO
    - Capital anterior: " . $this->formatMoney($old) . "
    - Capital atual: " . $this->formatMoney($new) . "
    - Variação absoluta: " . $this->formatMoney($increaseAbs) . "
    - Variação percentual: " . round($increaseRate * 100, 2) . "%
    
    CLASSIFICAÇÃO AML
    - Score de risco: {$score}
    - Nível de risco: {$riskLevel}
    - Motivo declarado: {$motivo}
    
    ANÁLISE DE RISCO
    Foi identificado um movimento de capital relevante associado a alteração contratual.
    Apesar de variação percentual moderada, o valor absoluto envolvido é significativo,
    o que pode indicar:
    
  ";

            $this->createAlert(
                $customer,
                "Aumento abrupto e injustificado do capital seguro entre apólices",
                $description,
                $riskLevel,
                $score
            );
        }
    }

    private function checkEarlyRedemption(
        Entities $customer,
        array $policies,
        array $receipts = [],
        array $refunds = []
    ): void {

        foreach ($policies as $p) {

            $estado = strtolower(trim($this->get($p, 'estado_apolice')));
            if (!in_array($estado, ['cancelled', 'terminated'])) continue;

            $inicio = $this->safeDate($this->get($p, 'data_inicio'));
            $fim = $this->safeDate($this->get($p, 'data_anulacao') ?? $this->get($p, 'data_fim'));

            if (!$inicio || !$fim) continue;

            $dias = $inicio->diffInDays($fim);
            if ($dias <= 0 || $dias > 365) continue;

            $apolice = $this->get($p, 'numero_apolice');

            /* =========================
               💰 ENTRADAS (RECIBOS)
            ========================== */

            $totalPago = array_sum(array_map(
                fn($r) => ($r['numero_apolice'] ?? null) === $apolice
                    ? (float)($r['valor_pago'] ?? 0)
                    : 0,
                $receipts
            ));

            /* =========================
               💸 SAÍDAS (ESTORNOS)
            ========================== */

            $totalEstorno = array_sum(array_map(
                fn($r) => ($r['n_apolice'] ?? null) === $apolice
                    ? (float)($r['valor_total'] ?? 0)
                    : 0,
                $refunds
            ));

            if ($totalPago <= 0) continue;

            $perda = $totalPago - $totalEstorno;

            if ($perda <= 0) continue;

            $percentualPerda = $perda / $totalPago;

            if ($percentualPerda < 0.10) continue;

            $score = 20;

            if ($percentualPerda >= 0.20) $score += 10;
            if ($dias <= 180) $score += 10;

            $description =
                "
    
    Cliente: {$customer->customer_number}
    Apólice: {$apolice}
    
     Fluxo financeiro:
    - Pago: " . $this->formatMoney($totalPago) . "
    - Estorno: " . $this->formatMoney($totalEstorno) . "
    - Perda: " . $this->formatMoney($perda) . "
    - Percentual: " . round($percentualPerda * 100, 2) . "%
    - Duração: {$dias} dias
    
    AML:
    Resgate antecipado com perda financeira aceite,
    compatível com layering de fundos e ausência de racional económico.";

            $this->createAlert(
                $customer,
                'Resgate ou cancelamento da apólice antes de 12 meses',
                $description,
                'Alto',
                $score
            );
        }
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
        array $beneficiaries = []
    ): void {

        Log::info('🚀 KYT PRODUCT BENEFICIARY ANALYSIS START', [
            'customer' => $customer->customer_number,
            'records_received' => count($beneficiaries)
        ]);

        if (empty($beneficiaries)) {
            Log::warning('⚠️ KYT EXIT - EMPTY BENEFICIARIES');
            return;
        }

        /* =========================
           NORMALIZAÇÃO CRÍTICA
        ========================== */

        $beneficiaries = collect($beneficiaries)
            ->map(function ($b) {

                $b = (array) $b;

                return [
                    'numero_apolice' => trim((string)($b['numero_apolice'] ?? '')),
                    'descricao_produto' => strtoupper(trim($b['descricao_produto'] ?? 'UNKNOWN')),
                    'codigo_beneficiario' => trim((string)($b['codigo_beneficiario'] ?? '')),
                    'nome_beneficiario' => strtoupper(trim($b['nome_beneficiario'] ?? '')),
                    'tipo_beneficiario' => strtoupper(trim($b['tipo_beneficiario'] ?? '')),
                    'percentagem_atribuida' => (float)($b['percentagem_atribuida'] ?? 0),
                    'data' => $b['data_atualizacao_beneficiario'] ?? null,
                ];
            })
            ->filter(fn($b) => $b['numero_apolice'] !== '')
            ->values();

        Log::info('📦 NORMALIZED BENEFICIARIES', [
            'count' => $beneficiaries->count()
        ]);

        /* =========================
           GROUP BY PRODUCT
        ========================== */

        $grouped = $beneficiaries->groupBy('descricao_produto');

        Log::info('📊 GROUPS CREATED', [
            'total_groups' => $grouped->count(),
            'products' => $grouped->keys()
        ]);

        foreach ($grouped as $produto => $records) {

            Log::info('🔎 PROCESSING PRODUCT', [
                'produto' => $produto,
                'records' => $records->count()
            ]);

            if ($records->count() < 2) {
                Log::info('⛔ SKIP PRODUCT (INSUFFICIENT DATA)', [
                    'produto' => $produto
                ]);
                continue;
            }

            $records = $records->sortBy(
                fn($r) =>
                $this->safeDate($r['data'])?->timestamp ?? 0
            )->values();

            $history = [];
            $changes = 0;
            $prev = null;

            foreach ($records as $r) {

                $beneficiaryId = $r['codigo_beneficiario']
                    ?: md5($r['nome_beneficiario'] . $r['tipo_beneficiario']);

                $history[] = $beneficiaryId;

                if ($prev !== null && $prev !== $beneficiaryId) {
                    $changes++;
                }

                $prev = $beneficiaryId;
            }

            $unique = count(array_unique($history));

            Log::info('📈 ANALYSIS RESULT', [
                'produto' => $produto,
                'unique_beneficiaries' => $unique,
                'changes' => $changes
            ]);

            if ($unique < 3 || $changes < 2) {
                Log::info('⛔ RULE NOT TRIGGERED', [
                    'produto' => $produto
                ]);
                continue;
            }

            $dates = $records->map(
                fn($r) =>
                $this->safeDate($r['data'])
            )->filter();

            if ($dates->isEmpty()) {
                Log::warning('❌ NO VALID DATES', [
                    'produto' => $produto
                ]);
                continue;
            }

            $min = $dates->min();
            $max = $dates->max();
            $days = $min->diffInDays($max);

            if ($days > 365) {
                Log::info('⛔ TIME RANGE EXCEEDED', [
                    'produto' => $produto,
                    'days' => $days
                ]);
                continue;
            }


            /* =========================
               SCORE KYT
            ========================== */

            $score = 20;

            if ($changes >= 3) $score += 10;
            if ($changes >= 4) $score += 15;
            if ($changes >= 5) $score += 20;

            if ($unique >= 3) $score += 10;
            if ($unique >= 4) $score += 15;

            Log::warning('🚨 KYT ALERT TRIGGERED', [
                'produto' => $produto,
                'score' => $score
            ]);
            $apolicesDetalhadas = $records
                ->groupBy('numero_apolice')
                ->map(function ($items, $apolice) {
                    return "- Apólice: {$apolice} | Registos: " . $items->count();
                })
                ->implode("\n");

            $beneficiaryList = collect($records)
                ->map(function ($r) {
                    return "- Nome: {$r['nome_beneficiario']}
  Tipo: {$r['tipo_beneficiario']}
  ID Beneficiário: {$r['codigo_beneficiario']}
  Apólice: {$r['numero_apolice']}
  Percentagem: {$r['percentagem_atribuida']}%";
                })
                ->implode("\n\n");

            $apolicesUnicas = $records
                ->pluck('numero_apolice')
                ->unique()
                ->implode(', ');

            $description = "
KYT - ALTERAÇÃO FREQUENTE DE BENEFICIÁRIOS

Cliente: {$customer->customer_number}
Produto: {$produto}

 APÓLICES ENVOLVIDAS:
{$apolicesDetalhadas}

 RESUMO GLOBAL:
- Apólices afetadas: {$apolicesUnicas}
- Beneficiários distintos: {$unique}
- Número de alterações: {$changes}
- Período analisado: {$min->format('Y-m-d')} → {$max->format('Y-m-d')}
- Duração: {$days} dias

 BENEFICIÁRIOS IDENTIFICADOS:
{$beneficiaryList}

ANÁLISE DE RISCO:
Foi identificado um padrão de alterações de beneficiários distribuído por múltiplas apólices do mesmo produto.
Este comportamento pode indicar reorganização de beneficiários ou tentativa de diluição de beneficiário final (UBO).

";

            $this->createAlert(
                $customer,
                'Mudanças frequentes de beneficiários sem justificação aparente',
                $description,
                'Alto',
                $score
            );
        }

        Log::info('🏁 KYT PRODUCT BENEFICIARY ANALYSIS FINISHED');
    }


    private function checkHighRiskGeography(
        Entities $customer,
        array $policies,
        array $receipts = [],
        array $beneficiaries = []
    ): void {

        Log::info('🌍 KYT HIGH RISK GEOGRAPHY START', [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies),
            'receipts_count' => count($receipts),
            'beneficiaries_count' => count($beneficiaries)
        ]);

        /* =========================
           NORMALIZAÇÃO
        ========================== */

        $policies = collect($policies)->map(fn($p) => (array) $p);
        $beneficiaries = collect($beneficiaries)->map(fn($b) => (array) $b);
        $receipts = collect($receipts)->map(fn($r) => (array) $r);

        /* =========================
           CARREGAR PAÍSES DE RISCO (CACHE OPTIMIZED)
        ========================== */

        $highRiskCountries = Cache::remember('ky_high_risk_countries', 3600, function () {
            return IndicatorType::where('indicator_id', 9)
                ->where('score', '>=', 3)
                ->pluck('description')
                ->map(fn($c) => strtoupper(trim($c)))
                ->toArray();
        });

        Log::info('📌 HIGH RISK COUNTRIES LOADED', [
            'count' => count($highRiskCountries)
        ]);

        /* =========================
           LOOP APÓLICES
        ========================== */

        foreach ($policies as $policy) {

            $apolice = $policy['numero_apolice'] ?? null;

            if (!$apolice) {
                Log::warning('⚠️ POLICY WITHOUT APOLICE', ['policy' => $policy]);
                continue;
            }

            Log::info('🔎 PROCESSING APOLICE', ['apolice' => $apolice]);

            $countriesDetected = [];

            /* =========================
               BENEFICIÁRIOS
            ========================== */

            $beneficiariosApolice = $beneficiaries->where('numero_apolice', $apolice);

            foreach ($beneficiariosApolice as $b) {

                $pais = $this->normalizeCountry($b['pais_residencia_beneficiario'] ?? null);

                if ($pais) {
                    $countriesDetected[] = $pais;

                    Log::info('👤 BENEFICIARY COUNTRY', [
                        'apolice' => $apolice,
                        'country' => $pais
                    ]);
                }
            }

            /* =========================
               RECIBOS
            ========================== */

            $recibosApolice = $receipts->where('numero_apolice', $apolice);

            foreach ($recibosApolice as $r) {

                $pais = $this->normalizeCountry($r['pais_iban_origem'] ?? null);

                if (!$pais && !empty($r['iban_origem'])) {
                    $pais = $this->extractCountryFromIBAN($r['iban_origem']);
                }

                if ($pais) {
                    $countriesDetected[] = $pais;

                    Log::info('💰 RECEIPT COUNTRY', [
                        'apolice' => $apolice,
                        'country' => $pais
                    ]);
                }
            }

            $countriesDetected = array_unique($countriesDetected);

            Log::info('📊 COUNTRIES DETECTED', [
                'apolice' => $apolice,
                'countries' => $countriesDetected
            ]);

            if (empty($countriesDetected)) {
                Log::warning('⛔ NO COUNTRIES DETECTED', ['apolice' => $apolice]);
                continue;
            }

            /* =========================
               MATCH RISCO
            ========================== */

            $riskCountries = array_intersect($countriesDetected, $highRiskCountries);

            Log::info('⚠️ RISK CHECK', [
                'apolice' => $apolice,
                'risk_matches' => $riskCountries
            ]);

            if (empty($riskCountries)) {
                Log::info('ℹ️ NO HIGH RISK MATCH', ['apolice' => $apolice]);
                continue;
            }

            /* =========================
               SCORE
            ========================== */

            $score = 25;

            /* =========================
               ALERTA
            ========================== */

            $description = sprintf(
                "KYT - EXPOSIÇÃO GEOGRÁFICA DE ALTO RISCO
    
    IDENTIFICAÇÃO
    - Cliente: %s
    - Apólice: %s
    
    EXPOSIÇÃO DETECTADA
    - Países identificados: %s
    - Países de alto risco: %s
    
    FONTES
    - Beneficiários
    - Recibos e pagamentos
    - IBAN de origem
    
    INTERPRETAÇÃO AML
    Exposição a jurisdições sensíveis associada a fluxos financeiros internacionais,
    com potencial risco de estruturação ou ocultação de beneficiário efetivo (UBO).
    
    DATA: %s",
                $customer->customer_number,
                $apolice,
                implode(', ', $countriesDetected),
                implode(', ', $riskCountries),
                now()->format('Y-m-d H:i:s')
            );

            $this->createAlert(
                $customer,
                'Apólices com beneficiários ou pagamentos de jurisdições de alto risco',
                $description,
                'Alto',
                $score
            );

            Log::warning('🚨 ALERT CREATED', [
                'apolice' => $apolice,
                'score' => $score
            ]);
        }

        Log::info('🏁 KYT HIGH RISK GEOGRAPHY FINISHED');
    }

    /* =========================
       ALERT CREATION
    ========================== */
    private function extractCountryFromIBAN(string $iban): ?string
    {
        $iban = strtoupper(trim($iban));

        if (strlen($iban) < 2) return null;

        return substr($iban, 0, 2); // ex: AO, PT, GB
    }
    private function formatMoney($value): string
    {
        return number_format((float)$value, 2, '.', ' ');
    }
    private function normalizeCountry(?string $country): ?string
    {
        if (!$country) return null;

        return strtoupper(trim($country));
    }
    private function toArray($data): array
    {
        if (is_array($data)) return $data;

        if (is_object($data)) {
            return json_decode(json_encode($data), true);
        }

        return [];
    }
    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        $invalid = ['ANULADA', 'TERMINADA', 'INACTIVOS', 'NORMAL', ''];
        if (in_array(strtoupper(trim($date)), $invalid)) return null;

        try {
            $dt = preg_replace('/\.\d+$/', '', $date);
            return Carbon::parse($dt)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function get($array, string $key, $default = null)
    {
        return $array[$key] ?? $default;
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



    private function formatPolicyContext(array $policy): string
    {
        $produto = $policy['descricao_produto'] ?? 'N/A';
        $apolice = $policy['numero_apolice'] ?? 'N/A';

        $premium = $this->money($this->getPolicyAmount($policy));
        $capital = $this->money((float) ($policy['capital'] ?? 0));

        $inicio = $policy['data_inicio'] ?? 'N/A';
        $fim = $policy['data_fim'] ?? 'N/A';

        return "
Produto: {$produto}
Prémio pago: {$premium}
Capital segurado: {$capital}
Data início: {$inicio}
Data fim: {$fim}
";
    }
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

            Log::warning("ALERT CREATED", [
                'type' => $type,
                'customer' => $customer->customer_number
            ]);
        }
    }
}
