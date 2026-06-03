<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use App\Models\Entities\RiskAssessment;
use App\Enum\TypeEntity;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class KYTService
{
    public function RiskAssessmentEntity(Entities $customer): array
    {
        $cacheKey = "risk_assessment_entity_{$customer->id}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $risk = RiskAssessment::where('entity_id', $customer->id)
            ->latest()
            ->first();

        if (!$risk) {
            Log::warning("Avaliação de risco ausente para cliente {$customer->customer_number}");

            $data = [
                'risk_id' => null,
                'alert_priority' => false,
                'valid' => false
            ];

            Cache::put($cacheKey, $data, now()->addHours(20));

            return $data;
        }

        $isHighRisk = in_array($risk->diligence, ["Cliente Inaceitável", "Reforçada"]);

        if ($isHighRisk) {
            Log::warning(
                "Cliente {$customer->customer_number} com avaliação de risco {$risk->diligence} (ID: {$risk->id})"
            );
        }

        $data = [
            'risk_id' => $risk->id,
            'alert_priority' => $isHighRisk,
            'valid' => !$isHighRisk
        ];

        Cache::put($cacheKey, $data, now()->addHours(20));

        return $data;
    }

    public function runAllChecksMemory(
        Entities $customer,
        array $policies,
        array $changes = [],
        array $refunds = [],
        array $receipts = [],
        array $beneficiaries = []
    ): void {
        $policies = $this->normalizePolicies($policies);

        Log::info("KYT START", [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies)
        ]);

        if (empty($policies)) return;

        $this->checkAbruptCapitalIncrease($customer, $policies, $changes);
        $this->checkPolicyLifecycleAbuse($customer, $policies, $changes, $refunds);
        $this->checkHighPremiumLowRisk($customer, $policies);
        $this->checkMultipleShortPolicies($customer, $policies);
        $this->checkThirdPartyPayments($customer, $policies, $receipts);
        $this->checkFrequentBeneficiaryChanges($customer, $policies, $changes, $beneficiaries);
        $this->checkHighRiskGeography($customer, $policies, $receipts);
        $this->checkOverpaymentRefund($customer, $policies, $refunds, $receipts);

        Log::info("KYT FINISHED", ['customer' => $customer->customer_number]);
    }

    /* =========================
       NORMALIZAÇÃO
    ========================== */

    private function normalizePolicies(array $policies): array
    {
        return array_map(function ($p) {
            $p = (array) $p;
            return [
                'numero_apolice' => $p['Numero_Apolice'] ?? $p['numero_apolice'] ?? null,
                'numero_cliente' => $p['Numero_Cliente'] ?? $p['numero_cliente'] ?? null,
                'descricao_produto' => strtoupper(trim($p['Descricao_Produto'] ?? $p['descricao_produto'] ?? '')),
                'estado_apolice' => $this->normalizeStatus($p['Estado_Apolice'] ?? $p['estado_apolice'] ?? null),
                'data_inicio' => $this->parseDate($p['Data_Inicio'] ?? $p['data_inicio'] ?? null),
                'data_fim' => $this->parseDate($p['Data_Fim'] ?? $p['data_fim'] ?? null),
                'capital' => $this->toFloat($p['Capital'] ?? $p['capital'] ?? 0),
                'premium_total' => $this->toFloat($p['Premio_Total'] ?? $p['premium_total'] ?? 0),
                'interest' => $this->toFloat($p['Juros'] ?? $p['interest'] ?? 0),
            ];
        }, $policies);
    }
    private function toFloat($value): float
    {
        return is_numeric($value) ? (float)$value : 0.0;
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtoupper(trim($status ?? ''));
        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'CANCELADA', 'C/ CARTA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS', 'Anulada' => 'terminated',
            default => 'unknown'
        };
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

    private function safeDays(?string $start, ?string $end): ?int
    {
        try {
            if (!$start || !$end) return null;
            return Carbon::parse($start)->diffInDays(Carbon::parse($end));
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatMoney($value): string
    {
        return number_format((float)$value, 2, '.', ' ');
    }

    private function isCollective(Entities $customer): bool
    {
        return (int)($customer->entity_type ?? 0) === TypeEntity::COLECTIVA->value;
    }

    private function filterRelevantPolicies(array $policies, array $relevant, array $excluded = []): array
    {
        return array_values(array_filter($policies, function ($p) use ($relevant, $excluded) {
            $product = strtoupper(trim($p['descricao_produto'] ?? ''));
            if ($excluded && in_array($product, $excluded)) return false;
            return in_array($product, $relevant);
        }));
    }

    private function sortPoliciesByDate(array $policies): array
    {
        usort($policies, fn($a, $b) =>
            strtotime($a['data_inicio'] ?? '1970') <=> strtotime($b['data_inicio'] ?? '1970')
        );
        return $policies;
    }

    private function formatPolicyList(array $policies): string
    {
        return implode(', ', array_map(fn($p) =>
            $p['numero_apolice'] . ' (' . ($p['descricao_produto'] ?? 'N/A') . ')', $policies
        ));
    }

    private function collectPolicyNums(array $policies, array $relevant, array $excluded = []): array
    {
        $nums = [];
        foreach ($policies as $p) {
            $product = strtoupper(trim($p['descricao_produto'] ?? ''));
            if ($excluded && in_array($product, $excluded)) continue;
            if (in_array($product, $relevant)) {
                $nums[] = $p['numero_apolice'];
            }
        }
        return $nums;
    }

    /* =========================
       REGRA KYT - AUMENTO ABRUPTO DE CAPITAL
    ========================== */

    private function checkAbruptCapitalIncrease(Entities $customer, array $policies, array $changes = []): void
    {
        $isCollective = $this->isCollective($customer);

        $relevantProducts = $isCollective
            ? [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'VIDA RISCO GRUPO',
                'GRUPO-CAPITAL P/ADERENTE',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ]
            : [
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL',
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO',
                'SEGURO BAI VIDA',
                'SEGURO VIDA-FIXE',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'SEGURO VIDA CRÉDITO',
                'SEGURO VIDA CRÉDITO (AKZ)',
                'SEG. VIDA CRÉDITO PENSIONISTA',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];

        $threshold30 = $isCollective ? 0.60 : 0.40;
        $threshold90 = $isCollective ? 1.00 : 0.70;

        $justifiedMotives = ['herança', 'mudança de emprego', 'promoção', 'evento económico'];
        foreach ($changes as $change) {
            $motivo = strtolower(trim($change->motivo_alteracao ?? ''));
            if (in_array($motivo, $justifiedMotives)) {
                Log::info("KYT aumento justificado para {$customer->customer_number}: {$motivo}");
                return;
            }
        }

        $grouped = [];
        foreach ($policies as $p) {
            $product = strtoupper(trim($p['descricao_produto'] ?? ''));
            if (!in_array($product, $relevantProducts)) continue;
            if (($p['capital'] ?? 0) <= 0) continue;
            $grouped[$product][] = $p;
        }

        foreach ($grouped as $product => $group) {
            if (count($group) < 2) continue;

            $group = $this->sortPoliciesByDate($group);

            $first = $group[0];
            $firstCapital = (float)($first['capital'] ?? 0);
            $firstDate = $this->safeDate($first['data_inicio']);
            if ($firstCapital <= 0 || !$firstDate) continue;

            $pairs = [];

            for ($i = 1; $i < count($group); $i++) {
                $curr = $group[$i];
                $currCapital = (float)($curr['capital'] ?? 0);
                $currDate = $this->safeDate($curr['data_inicio']);
                if ($currCapital <= 0 || !$currDate) continue;

                $increaseRate = ($currCapital - $firstCapital) / $firstCapital;
                if ($increaseRate <= 0) continue;

                $daysDiff = $firstDate->diffInDays($currDate);

                $isTrigger = ($daysDiff <= 30 && $increaseRate >= $threshold30)
                          || ($daysDiff <= 90 && $increaseRate >= $threshold90);

                if (!$isTrigger) continue;

                $pairs[] = [
                    'policy' => $curr['numero_apolice'],
                    'capital' => $currCapital,
                    'data' => $curr['data_inicio'],
                    'increase' => $increaseRate * 100,
                    'days' => $daysDiff,
                ];
            }

            if (empty($pairs)) continue;

            $entityLabel = $isCollective ? 'Coletiva' : 'Singular';

            $policiesList = implode("\n", array_map(fn($pair) => sprintf(
                "  - %s | Capital: %s | +%.2f%% em %d dias",
                $pair['policy'],
                $this->formatMoney($pair['capital']),
                $pair['increase'],
                $pair['days']
            ), $pairs));

            $description = sprintf(
                "AUMENTO ABRUPTO DE CAPITAL - %s\n" .
                "Cliente: %s\n" .
                "Produto: %s\n\n" .
                "Apólice de referência:\n" .
                "  N.º: %s | Capital: %s | Início: %s\n\n" .
                "Apólices com aumento detetado:\n%s\n\n" .
                "Interpretação AML:\n" .
                "Aumento significativo de capital sem justificação económica\n" .
                "compatível com perfil de risco elevado (layering/estruturação).",
                $entityLabel,
                $customer->customer_number,
                $product,
                $first['numero_apolice'],
                $this->formatMoney($firstCapital),
                $first['data_inicio'],
                $policiesList
            );

            $this->createAlert(
                $customer,
                'Aumento abrupto de capital entre apólices',
                $description,
                'Alto',
                25
            );
        }
    }

    /* =========================
       REGRA KYT - ABUSO DO CICLO DE VIDA DAS APÓLICES (2,5,6)
    ========================== */

    private function checkPolicyLifecycleAbuse(
        Entities $customer,
        array $policies,
        array $changes = [],
        array $refunds = []
    ): void {
        $isCollective = $this->isCollective($customer);

        $relevantProducts = $isCollective
            ? [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'GRUPO-CAPITAL P/ADERENTE',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ]
            : [
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL',
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO',
                'SEGURO BAI VIDA',
                'SEGURO VIDA-FIXE',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];

        $minEvents = $isCollective ? 3 : 2;
        $maxDays = $isCollective ? 90 : 60;
        $minPremium = $isCollective ? 10000000.00 : 1000000.00;

        $filtered = $this->filterRelevantPolicies($policies, $relevantProducts);
        $filtered = array_values(array_filter($filtered, fn($p) => ($p['premium_total'] ?? 0) > 0));

        if (count($filtered) < $minEvents) return;

        $filtered = $this->sortPoliciesByDate($filtered);

        $events = [];
        foreach ($filtered as $p) {
            $inicio = $this->safeDate($p['data_inicio'] ?? null);
            $fim = $this->safeDate($p['data_fim'] ?? null);
            if (!$inicio || !$fim) continue;

            $estado = $p['estado_apolice'] ?? '';
            $isCancelled = in_array($estado, ['cancelled', 'terminated']);

            $temResgate = false;
            foreach ($refunds as $r) {
                if (($r['Numero_Apolice'] ?? null) === $p['numero_apolice']) {
                    $temResgate = true;
                    break;
                }
            }

            if (!$isCancelled && !$temResgate) continue;

            $events[] = $p;
        }

        if (count($events) < $minEvents) return;

        $windowStart = $this->safeDate($events[0]['data_inicio']);
        $windowEnd = $this->safeDate($events[count($events) - 1]['data_inicio']);
        if (!$windowStart || !$windowEnd) return;

        $windowDays = $windowStart->diffInDays($windowEnd);
        if ($windowDays > $maxDays) return;

        $totalPremium = array_sum(array_column($events, 'premium_total'));
        if ($totalPremium < $minPremium) return;

        $entityLabel = $isCollective ? 'Coletiva' : 'Singular';

        $description = sprintf(
            "ABUSO DO CICLO DE VIDA DAS APÓLICES\n" .
            "Cliente: %s | Tipo: %s\n\n" .
            "Eventos detetados: %d\n" .
            "Janela temporal: %d dias (limiar: %d dias)\n" .
            "Prémio total: %s (limiar: %s)\n" .
            "Apólices: %s\n\n" .
            "Interpretação AML:\n" .
            "Cancelamentos, resgates ou substituições reiterados em curto período,\n" .
            "compatível com fragmentação de valores e reciclagem financeira.",
            $customer->customer_number,
            $entityLabel,
            count($events),
            $windowDays,
            $maxDays,
            $this->formatMoney($totalPremium),
            $this->formatMoney($minPremium),
            $this->formatPolicyList($events)
        );

        $score = 15;
        if ($totalPremium >= $minPremium * 2) $score += 5;
        if (count($events) >= $minEvents + 1) $score += 5;
        if ($windowDays <= $maxDays / 2) $score += 5;

        $this->createAlert(
            $customer,
            'Abuso do ciclo de vida das apólices',
            $description,
            'Alto',
            $score
        );
    }

    /* =========================
       REGRA KYT - PRÉMIO ELEVADO VS RISCO SEGURADO
    ========================== */

    private function checkHighPremiumLowRisk(Entities $customer, array $policies): void
    {
        $isCollective = $this->isCollective($customer);

        [$highRiskProducts, $lowRiskProducts, $threshold] = $isCollective
            ? [
                [
                    'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                    'GRUPO-CAPITAL PESSOAS DIVERSAS',
                    'PRÉMIO FIXO',
                    'PRÉMIO VARIÁVEL',
                    'FUNDO DE PENSÕES BAI',
                    'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
                ],
                [
                    'MULTI-RISCOS/ESTABELECIMENTOS',
                    'CIVIL PROFISSIONAL',
                    'EXPLORACAO INDUSTRIAL',
                ],
                0.25,
            ]
            : [
                [
                    'SEGURO VIDA CRÉDITO',
                    'SEGURO VIDA CRÉDITO (AKZ)',
                    'SEG. VIDA CRÉDITO PENSIONISTA',
                    'SEGURO BAI VIDA',
                    'PRÉMIO FIXO',
                    'PRÉMIO VARIÁVEL',
                    'SEGURO VIDA-FIXE',
                    'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
                ],
                [
                    'SEGURO ESCOLAR',
                    'ASSISTÊNCIA SAÚDE',
                    'VIAGEM',
                    'VIAGEM E ASSISTÊNCIA',
                ],
                0.10,
            ];

        $entityLabel = $isCollective ? 'Coletiva' : 'Singular';

        $grouped = [];
        foreach ($policies as $policy) {
            $product = strtoupper(trim($policy['descricao_produto'] ?? ''));
            if (in_array($product, $lowRiskProducts)) continue;
            $grouped[$product][] = $policy;
        }

        foreach ($grouped as $product => $group) {
            $triggering = array_filter($group, function ($p) use ($threshold) {
                $premium = (float)($p['premium_total'] ?? 0);
                $capital = (float)($p['capital'] ?? 0);
                if ($premium <= 0 || $capital <= 0) return false;
                return ($premium / $capital) >= $threshold;
            });

            if (empty($triggering)) continue;

            $totalPremium = array_sum(array_column($triggering, 'premium_total'));
            $totalCapital = array_sum(array_column($triggering, 'capital'));
            $avgRatio = $totalCapital > 0 ? $totalPremium / $totalCapital : 0;
            $maxRatio = max(array_map(fn($p) => ($p['premium_total'] ?? 0) / max(($p['capital'] ?? 1), 1), $triggering));
            $policyList = implode(', ', array_map(fn($p) => $p['numero_apolice'], $triggering));

            if ($isCollective) {
                $severity = $maxRatio >= 0.40 ? 'Alto' : 'Médio';
                $score = $maxRatio >= 0.40 ? 30 : 20;
            } else {
                $severity = 'Alto';
                $score = 25;
                if ($maxRatio >= 0.30) $score += 10;
            }

            $description = sprintf(
                "KYT HIGH PREMIUM LOW RISK - %s\n" .
                "Produto: %s\n" .
                "Apólices: %s\n" .
                "Prémio total: %s\n" .
                "Capital total: %s\n" .
                "Rácio médio prémio/capital: %.2f%%\n\n" .
                "Interpretação AML:\n" .
                "Prémio elevado incompatível com o risco segurado ou capacidade financeira do cliente.\n" .
                "Cenário alinhado ao Guia de Operações Suspeitas da ARSEG e às orientações do GAFI.",
                $entityLabel,
                $product,
                $policyList,
                $this->formatMoney($totalPremium),
                $this->formatMoney($totalCapital),
                $avgRatio * 100
            );

            $this->createAlert(
                $customer,
                'Prémio elevado incompatível com capacidade financeira',
                $description,
                $severity,
                $score
            );
        }
    }

    /* =========================
       REGRA KYT - MÚLTIPLAS APÓLICES CURTAS
    ========================== */

    private function checkMultipleShortPolicies(Entities $customer, array $policies): void
    {
        $isCollective = $this->isCollective($customer);

        if ($isCollective) {
            $relevantProducts = [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'VIDA RISCO GRUPO',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'AC.PESSOAIS GRUPO',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'MULTI-RISCOS/INDUSTRIA',
                'EMPRESAS CONSTRUÇAO CIVIL',
                'EXPLORACAO INDUSTRIAL',
            ];
            $minPolicies = 5;
            $maxDays = 90;
        } else {
            $relevantProducts = [
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO',
                'VIDA RISCO INDIVIDUAL',
                'VIAGEM',
                'VIAGEM E ASSISTÊNCIA',
                'VIAGEM E ASSISTÊNCIA AKZ',
                'AMPARO FAMILIAR',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'INCENDIO/RISCO INDUSTRIAL',
                'PETROQUÍMICA',
                'MINEIRO',
                'CONSTRUÇOES',
            ];
            $minPolicies = 3;
            $maxDays = 60;
        }

        $filtered = $this->filterRelevantPolicies($policies, $relevantProducts, $excludedProducts);
        if (count($filtered) < $minPolicies) return;

        $filtered = $this->sortPoliciesByDate($filtered);

        $windowStart = $this->safeDate($filtered[0]['data_inicio'] ?? null);
        $windowEnd = $this->safeDate($filtered[count($filtered) - 1]['data_inicio'] ?? null);
        if (!$windowStart || !$windowEnd) return;

        $windowDays = $windowStart->diffInDays($windowEnd);
        if ($windowDays > $maxDays) return;

        $totalPremium = array_sum(array_column($filtered, 'premium_total'));
        $entityLabel = $isCollective ? 'Coletiva' : 'Singular';

        $description = sprintf(
            "MÚLTIPLAS APÓLICES DE CURTA DURAÇÃO\n" .
            "Cliente: %s | Tipo: %s\n\n" .
            "Apólices detetadas: %d\n" .
            "Janela temporal: %d dias (limiar: %d dias)\n" .
            "Prémio total acumulado: %s\n" .
            "Apólices: %s\n\n" .
            "Interpretação AML:\n" .
            "Subscrição de múltiplas apólices de curta duração para fragmentar valores elevados,\n" .
            "compatível com estruturas de layering e smurfing (estruturação de prémios).",
            $customer->customer_number,
            $entityLabel,
            count($filtered),
            $windowDays,
            $maxDays,
            $this->formatMoney($totalPremium),
            $this->formatPolicyList($filtered)
        );

        $score = 20;
        if (count($filtered) >= $minPolicies + 2) $score += 10;
        if ($totalPremium >= 5000000) $score += 5;
        if ($windowDays <= $maxDays / 2) $score += 5;

        $this->createAlert(
            $customer,
            'Múltiplas apólices de curta duração',
            $description,
            'Alto',
            $score
        );
    }

    /* =========================
       REGRA KYT - PAGAMENTOS POR TERCEIROS
    ========================== */

    private function checkThirdPartyPayments(Entities $customer, array $policies, array $receipts = []): void
    {
        $isCollective = $this->isCollective($customer);

        if ($isCollective) {
            $relevantProducts = [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'SAUDE GRUPO',
                'AC TRABALHO/TRAB. C/PROPRIA',
            ];
            $threshold = 1000000.00;
        } else {
            $relevantProducts = [
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL',
                'SEGURO BAI VIDA',
                'PRÉMIO VARIÁVEL',
                'PRÉMIO FIXO',
                'SEGURO VIDA-FIXE',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'AUTOMOVEL CVA',
                'AUTOMOVEL CVA - AKZ',
                'VIAGEM',
                'ROUBO',
            ];
            $threshold = 300000.00;
        }

        $filtered = $this->filterRelevantPolicies($policies, $relevantProducts, $excludedProducts);
        if (empty($filtered)) return;

        $relevantPolicyNums = array_map(fn($p) => $p['numero_apolice'], $filtered);

        $thirdPartyReceipts = [];
        foreach ($receipts as $r) {
            $polNum = is_object($r)
                ? ($r->numero_apolice ?? null)
                : ($r['numero_apolice'] ?? null);
            if (!$polNum || !in_array($polNum, $relevantPolicyNums)) continue;

            $indicator = is_object($r)
                ? strtolower(trim($r->indicador_pagamento_terceiro ?? ''))
                : strtolower(trim($r['indicador_pagamento_terceiro'] ?? ''));
            $payerName = is_object($r)
                ? ($r->nome_pagador ?? '')
                : ($r['nome_pagador'] ?? '');
            $payerNif = is_object($r)
                ? ($r->nif_pagador ?? '')
                : ($r['nif_pagador'] ?? '');

            if ($indicator === 'sim' && $payerName) {
                $thirdPartyReceipts[$polNum][] = [
                    'payer' => $payerName,
                    'nif' => $payerNif,
                ];
            }
        }

        $totalPremium = array_sum(array_column($filtered, 'premium_total'));
        if ($totalPremium < $threshold && empty($thirdPartyReceipts)) return;

        $entityLabel = $isCollective ? 'Coletiva' : 'Singular';

        $thirdPartyDetails = '';
        if (!empty($thirdPartyReceipts)) {
            $lines = [];
            foreach ($thirdPartyReceipts as $polNum => $payers) {
                $uniquePayers = array_unique(array_map(fn($t) => $t['payer'], $payers));
                $lines[] = $polNum . ' → ' . implode(', ', $uniquePayers);
            }
            $thirdPartyDetails = "\nPagadores identificados:\n" . implode("\n", $lines) . "\n";
        }

        $description = sprintf(
            "PAGAMENTOS DE PRÉMIOS POR TERCEIROS\n" .
            "Cliente: %s | Tipo: %s\n\n" .
            "Prémio total detetado: %s\n" .
            "Limiar aplicado: %s\n" .
            "%s" .
            "Apólices envolvidas: %s\n\n" .
            "Interpretação AML:\n" .
            "Pagamentos de prémios realizados por terceiros sem relação clara com o segurado.\n" .
            "Cenário compatível com funding externo, contribuições por terceiros ou\n" .
            "pagamentos indiretos, conforme Guia de Operações Suspeitas da ARSEG.",
            $customer->customer_number,
            $entityLabel,
            $this->formatMoney($totalPremium),
            $this->formatMoney($threshold),
            $thirdPartyDetails,
            $this->formatPolicyList($filtered)
        );

        $score = 20;
        if ($totalPremium >= $threshold * 2) $score += 10;
        if (!empty($thirdPartyReceipts)) $score += 10;

        $this->createAlert(
            $customer,
            'Pagamentos de prémios por terceiros',
            $description,
            'Alto',
            $score
        );
    }

    /* =========================
       REGRA KYT - ALTERAÇÕES FREQUENTES DE BENEFICIÁRIOS
    ========================== */

    private function checkFrequentBeneficiaryChanges(Entities $customer, array $policies, array $changes = [], array $beneficiaries = []): void
    {
        $isCollective = $this->isCollective($customer);

        if ($isCollective) {
            $relevantProducts = [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'VIDA RISCO GRUPO',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'MULTI-RISCOS/ESTABELECIMENTOS',
                'CAUÇÃO',
                'CONSTRUÇOES',
            ];
            $minChanges = 2;
            $maxDays = 90;
        } else {
            $relevantProducts = [
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL',
                'SEGURO BAI VIDA',
                'SEGURO VIDA-FIXE',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'AUTOMOVEL',
                'INCENDIO/RISCO SIMPLES',
                'EQUIPAMENTO ELECTRONICO',
            ];
            $minChanges = 3;
            $maxDays = 180;
        }

        if (count($changes) < $minChanges) return;

        $relevantPolicyNums = $this->collectPolicyNums($policies, $relevantProducts, $excludedProducts);
        if (empty($relevantPolicyNums)) return;

        $justifiedMotives = ['herança', 'casamento', 'divórcio', 'nascimento', 'óbito', 'falecimento', 'alteração familiar'];

        $beneficiaryMap = [];
        foreach ($beneficiaries as $b) {
            $bPolNum = is_object($b)
                ? ($b->numero_apolice ?? null)
                : ($b['numero_apolice'] ?? null);
            $bName = is_object($b)
                ? ($b->nome_beneficiario ?? '')
                : ($b['nome_beneficiario'] ?? '');
            if ($bPolNum && $bName) {
                $beneficiaryMap[$bPolNum][] = $bName;
            }
        }

        $beneficiaryChanges = [];
        foreach ($changes as $change) {
            $polNum = is_object($change)
                ? ($change->numero_apolice ?? null)
                : ($change['numero_apolice'] ?? null);

            if (!$polNum || !in_array($polNum, $relevantPolicyNums)) continue;

            $changeDate = is_object($change)
                ? $this->safeDate($change->data_alteracao ?? $change->created_at ?? null)
                : $this->safeDate($change['data_alteracao'] ?? $change['created_at'] ?? null);

            if (!$changeDate) continue;

            $motive = is_object($change)
                ? strtolower(trim($change->motivo_alteracao ?? ''))
                : strtolower(trim($change['motivo_alteracao'] ?? ''));

            if (in_array($motive, $justifiedMotives)) continue;

            $product = '';
            foreach ($policies as $p) {
                if ($p['numero_apolice'] === $polNum) {
                    $product = $p['descricao_produto'];
                    break;
                }
            }

            $bNames = array_unique($beneficiaryMap[$polNum] ?? []);

            $beneficiaryChanges[] = [
                'date' => $changeDate,
                'polNum' => $polNum,
                'product' => $product,
                'beneficiaries' => $bNames,
            ];
        }

        if (count($beneficiaryChanges) < $minChanges) return;

        usort($beneficiaryChanges, fn($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

        $firstDate = $beneficiaryChanges[0]['date'];
        $lastDate = $beneficiaryChanges[count($beneficiaryChanges) - 1]['date'];
        $windowDays = $firstDate->diffInDays($lastDate);

        if ($windowDays > $maxDays) return;

        $entityLabel = $isCollective ? 'Coletiva' : 'Singular';

        $polDetails = [];
        foreach ($beneficiaryChanges as $c) {
            $detail = $c['polNum'] . ' (' . $c['product'] . ')';
            if (!empty($c['beneficiaries'])) {
                $detail .= ' [Beneficiários: ' . implode(', ', $c['beneficiaries']) . ']';
            }
            $polDetails[] = $detail;
        }
        $polDetails = array_unique($polDetails);

        $description = sprintf(
            "ALTERAÇÕES FREQUENTES DE BENEFICIÁRIOS\n" .
            "Cliente: %s | Tipo: %s\n\n" .
            "Alterações detetadas: %d\n" .
            "Janela temporal: %d dias (limiar: %d dias)\n" .
            "Apólices envolvidas:\n%s\n\n" .
            "Interpretação AML:\n" .
            "Alterações frequentes de beneficiários sem fundamento económico ou familiar plausível,\n" .
            "compatível com tentativas de ocultação de beneficiário efetivo ou transferência indireta de valores.",
            $customer->customer_number,
            $entityLabel,
            count($beneficiaryChanges),
            $windowDays,
            $maxDays,
            implode("\n", $polDetails)
        );

        $score = 20;
        if (count($beneficiaryChanges) >= $minChanges + 1) $score += 10;
        if ($windowDays <= $maxDays / 2) $score += 5;

        $this->createAlert(
            $customer,
            'Alterações frequentes de beneficiários',
            $description,
            'Alto',
            $score
        );
    }

    /* =========================
       REGRA KYT - ALTO RISCO GEOGRÁFICO
    ========================== */

    private function checkHighRiskGeography(Entities $customer, array $policies, array $receipts = []): void
    {
        $isCollective = $this->isCollective($customer);

        if ($isCollective) {
            $relevantProducts = [
                'MERCADORIA TRANSPORTADAS/MARITIMO',
                'MERCADORIA TRANSPORTADAS/RODOVIÁRIO',
                'MERCADORIA TRANSPORTADAS/FERROVIÁRIO',
                'MERCADORIA TRANSPORTADAS/AEREO',
                'CASCO',
                'EMBARCACOES DE RECREIO',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'MULTI-RISCOS/HABITACAO',
                'MRH BANCA',
            ];
            $threshold = 1500000.00;
        } else {
            $relevantProducts = [
                'VIAGEM',
                'VIAGEM E ASSISTÊNCIA',
                'VIAGEM E ASSISTÊNCIA AKZ',
                'EMBARCACOES DE RECREIO',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'SAÚDE MWANGOLÉ',
                'SEGURO ESCOLAR',
                'AMPARO FAMILIAR',
            ];
            $threshold = 250000.00;
        }

        $filtered = $this->filterRelevantPolicies($policies, $relevantProducts, $excludedProducts);
        if (empty($filtered)) return;

        $totalPremium = array_sum(array_column($filtered, 'premium_total'));
        if ($totalPremium < $threshold) return;

        $relevantPolicyNums = array_map(fn($p) => $p['numero_apolice'], $filtered);

        $countries = [];
        foreach ($receipts as $r) {
            $polNum = is_object($r)
                ? ($r->numero_apolice ?? null)
                : ($r['numero_apolice'] ?? null);
            if (!$polNum || !in_array($polNum, $relevantPolicyNums)) continue;

            $pais = is_object($r)
                ? ($r->pais_iban_origem ?? '')
                : ($r['pais_iban_origem'] ?? '');
            if ($pais) {
                $countries[$polNum] = strtoupper(trim($pais));
            }
        }

        $entityLabel = $isCollective ? 'Coletiva' : 'Singular';

        $geoDetails = '';
        if (!empty($countries)) {
            $lines = [];
            foreach ($countries as $polNum => $pais) {
                $lines[] = $polNum . ' → ' . $pais;
            }
            $geoDetails = "\nPaíses de origem dos pagamentos:\n" . implode("\n", $lines) . "\n";
        }

        $description = sprintf(
            "ALTO RISCO GEOGRÁFICO\n" .
            "Cliente: %s | Tipo: %s\n\n" .
            "Prémio total detetado: %s\n" .
            "Limiar aplicado: %s\n" .
            "%s" .
            "Apólices envolvidas: %s\n\n" .
            "Interpretação AML:\n" .
            "Relações financeiras com jurisdições classificadas como de alto risco pelo GAFI.\n" .
            "Cenário compatível com fluxos financeiros transfronteiriços sem justificação\n" .
            "económica aparente, conforme Guia de Operações Suspeitas da ARSEG.",
            $customer->customer_number,
            $entityLabel,
            $this->formatMoney($totalPremium),
            $this->formatMoney($threshold),
            $geoDetails,
            $this->formatPolicyList($filtered)
        );

        $score = 20;
        if ($totalPremium >= $threshold * 2) $score += 10;
        if (!empty($countries)) $score += 5;

        $this->createAlert(
            $customer,
            'Alto risco geográfico',
            $description,
            'Alto',
            $score
        );
    }

    /* =========================
       REGRA KYT - SOBREPAGAMENTO COM REEMBOLSO
    ========================== */

    private function checkOverpaymentRefund(Entities $customer, array $policies, array $refunds = [], array $receipts = []): void
    {
        $isCollective = $this->isCollective($customer);

        if ($isCollective) {
            $relevantProducts = [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'SAUDE GRUPO',
                'MULTI-RISCOS/INDUSTRIA',
                'CONSTRUÇOES',
            ];
        } else {
            $relevantProducts = [
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL',
                'SEGURO BAI VIDA',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'SEGURO VIDA-FIXE',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $excludedProducts = [
                'AUTOMOVEL',
                'ROUBO',
                'PROTECÇÃO CONTRA ASSALTOS',
            ];
        }

        $relevantPolicyNums = $this->collectPolicyNums($policies, $relevantProducts, $excludedProducts);
        if (empty($relevantPolicyNums)) return;

        $entityLabel = $isCollective ? 'Coletiva' : 'Singular';

        $policyMap = [];
        foreach ($policies as $p) {
            $policyMap[$p['numero_apolice']] = $p;
        }

        $receiptMap = [];
        foreach ($receipts as $r) {
            $rPolNum = is_object($r)
                ? ($r->numero_apolice ?? null)
                : ($r['numero_apolice'] ?? null);
            $rValor = (float)(is_object($r)
                ? ($r->valor_pago ?? 0)
                : ($r['valor_pago'] ?? 0));
            if ($rPolNum && $rValor > 0) {
                if (!isset($receiptMap[$rPolNum])) $receiptMap[$rPolNum] = 0;
                $receiptMap[$rPolNum] += $rValor;
            }
        }

        $grouped = [];
        foreach ($refunds as $refund) {
            $polNum = $refund['Numero_Apolice'] ?? $refund['numero_apolice'] ?? null;
            if (!$polNum || !in_array($polNum, $relevantPolicyNums)) continue;

            $policy = $policyMap[$polNum] ?? null;
            if (!$policy) continue;

            $product = strtoupper(trim($policy['descricao_produto'] ?? ''));
            $premium = (float)($policy['premium_total'] ?? 0);
            if ($premium <= 0) continue;

            $refundAmount = (float)($refund['Valor_Estorno'] ?? $refund['valor_estorno'] ?? 0);
            if ($refundAmount <= 0) continue;

            $overpaymentRatio = $refundAmount / $premium;
            if ($overpaymentRatio < 0.05) continue;

            $beneficiaryName = $refund['Nome_Beneficiario'] ?? $refund['nome_beneficiario'] ?? '';
            $isThirdParty = !empty($beneficiaryName)
                && strtolower(trim($beneficiaryName)) !== strtolower(trim($customer->social_denomination ?? ''));

            $actualPaid = $receiptMap[$polNum] ?? 0;

            $grouped[$product][] = [
                'polNum' => $polNum,
                'premium' => $premium,
                'refundAmount' => $refundAmount,
                'ratio' => $overpaymentRatio,
                'beneficiary' => $beneficiaryName,
                'isThirdParty' => $isThirdParty,
                'actualPaid' => $actualPaid,
            ];
        }

        foreach ($grouped as $product => $items) {
            $totalPremium = array_sum(array_column($items, 'premium'));
            $totalRefund = array_sum(array_column($items, 'refundAmount'));
            $maxRatio = max(array_column($items, 'ratio'));
            $hasThirdParty = collect($items)->contains('isThirdParty', true);
            $totalActualPaid = array_sum(array_column($items, 'actualPaid'));

            $policyList = implode(', ', array_map(fn($i) => $i['polNum'], $items));

            $severity = $maxRatio >= 0.20 ? 'Alto' : 'Médio';
            $score = $maxRatio >= 0.20 ? 30 : 20;
            if ($totalActualPaid > 0 && $totalActualPaid > $totalPremium) $score += 5;

            $actualPaidLine = $totalActualPaid > 0
                ? "\nValor total pago (recibos): {$this->formatMoney($totalActualPaid)}"
                : '';

            $description = sprintf(
                "SOBREPAGAMENTO COM REEMBOLSO - %s\n" .
                "Produto: %s\n" .
                "Apólices: %s\n" .
                "Prémio total: %s" .
                "%s\n" .
                "Valor total reembolsado: %s\n" .
                "Rácio máximo reembolso/prémio: %.2f%%\n" .
                "Envolve terceiros: %s\n\n" .
                "Interpretação AML:\n" .
                "Sobrepagamento de prémio seguido de pedido de reembolso,\n" .
                "compatível com esquemas de movimentação indireta de valores (structuring/refunding).",
                $entityLabel,
                $product,
                $policyList,
                $this->formatMoney($totalPremium),
                $actualPaidLine,
                $this->formatMoney($totalRefund),
                $maxRatio * 100,
                $hasThirdParty ? 'Sim' : 'Não'
            );

            $this->createAlert(
                $customer,
                'Sobrepagamento de prémio com reembolso',
                $description,
                $severity,
                $score
            );
        }
    }

    /* =========================
       2º Cenário: Subscrição de múltiplas apólices de curta duração para fragmentar valores elevados.
       (static — compatibilidade descendente)
    ========================== */

    /**
     * Particulares: ≥2 eventos (60 dias); ≥ AOA 1.000.000,00
     * Coletivas: ≥3 eventos (90 dias); ≥ AOA 10.000.000,00
     * Múltiplos resgates ou cancelamentos com substituição reiterados.
     *
     * Produtos:
     * - Seguro de Poupança Vida (SPV) INDIVIDUAL
     * - Seguro de Poupança Vida (SPV) INDIVIDUAL TEMPORARIO
     * - Seguro BAI Vida
     * - Seguro Vida-Fixe
     * - PRÉMIO FIXO
     * - PRÉMIO VARIÁVEL
     * - FUNDO DE PENSÕES ABERTO - NOSSA Reforma
     */
    public static function scenarioPolicyFragmenting(Entities $customer): array
    {
        $isCollective = (int)($customer->entity_type ?? 0) === \App\Enum\TypeEntity::COLECTIVA->value;

        if ($isCollective) {
            $produtos = [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'GRUPO-CAPITAL P/ADERENTE',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $baseDate = now()->subDays(5);
            $policies = [];
            $changes = [];
            $refunds = [];
            $i = 1;
            foreach ($produtos as $produto) {
                $polNum = "POL-FRAG-COL-{$i}";
                $start = (clone $baseDate)->addHours($i * 8);
                $end = (clone $start)->addDays(60);
                $policies[] = [
                    'numero_apolice' => $polNum,
                    'premium_total' => 1500000.00,
                    'capital' => 30000000.00,
                    'data_inicio' => $start->format('Y-m-d H:i:s'),
                    'data_fim' => $end->format('Y-m-d'),
                    'estado_apolice' => 'Anulada',
                    'descricao_produto' => $produto,
                ];
                $changes[] = [
                    'numero_apolice' => $polNum,
                    'tipo_alteracao' => 'CANCELAMENTO COM SUBSTITUICAO',
                    'valor_anterior' => 30000000.00,
                    'novo_valor' => 0.00,
                    'motivo_alteracao' => 'Substituição de apólice por novo contrato',
                ];
                $refunds[] = [
                    'Numero_Apolice' => $polNum,
                    'Valor_Estorno' => 1400000.00,
                    'Data_Estorno' => $end->format('Y-m-d'),
                    'Nome_Beneficiario' => $customer->social_denomination,
                ];
                $i++;
            }
            return [
                'policies' => $policies,
                'changes' => $changes,
                'refunds' => $refunds,
                'receipts' => [],
            ];
        }

        $produtos = [
            'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL',
            'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO',
            'SEGURO BAI VIDA',
        ];

        $baseDate = now()->subDays(3);
        $policies = [];
        $changes = [];
        $refunds = [];

        $i = 1;
        foreach ($produtos as $produto) {
            $polNum = "POL-FRAG-PART-{$i}";
            $start = (clone $baseDate)->addHours($i * 6);
            $end = (clone $start)->addDays(45);
            $policies[] = [
                'numero_apolice' => $polNum,
                'premium_total' => 350000.00,
                'capital' => 7000000.00,
                'data_inicio' => $start->format('Y-m-d H:i:s'),
                'data_fim' => $end->format('Y-m-d'),
                'estado_apolice' => 'Anulada',
                'descricao_produto' => $produto,
            ];
            $changes[] = [
                'numero_apolice' => $polNum,
                'tipo_alteracao' => 'RESGATE ANTECIPADO',
                'valor_anterior' => 7000000.00,
                'novo_valor' => 0.00,
                'motivo_alteracao' => 'Resgate total antes da maturidade',
            ];
            $refunds[] = [
                'Numero_Apolice' => $polNum,
                'Valor_Estorno' => 330000.00,
                'Data_Estorno' => $end->format('Y-m-d'),
                'Nome_Beneficiario' => $customer->social_denomination,
            ];
            $i++;
        }

        return [
            'policies' => $policies,
            'changes' => $changes,
            'refunds' => $refunds,
            'receipts' => [],
        ];
    }

    /* =========================
        UTILITÁRIOS
    ========================== */

    private function safeDate($date)
    {
        try {
            if (!$date) return null;
            if ($date === '0000-00-00' || $date === '1900-01-01') return null;

            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getPolicyAmount(array $policy): float
    {
        return (float) (
            $policy['premium_total']
            ?? $policy['Premio_Total']
            ?? $policy['premio_simples']
            ?? 0
        );
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' Kz';
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

    /* =========================
       ALERTAS
    ========================== */

    private function createAlert(
        Entities $customer,
        string $type,
        string $description,
        string $severity,
        int $score
    ): void {
        $riskData = $this->RiskAssessmentEntity($customer);
        $alert = Alert::updateOrCreate(
            [
                'entity_id' => $customer->id,
                'type' => $type,
                'description' => $description,
            ],
            [
                'alert_priority' => $riskData['alert_priority'],
                'risk_assessment_id' => $riskData['risk_id'],
                'category' => 'KYT',
                'level' => $severity,
                'name' => $customer->social_denomination,
                'score' => $score,
            ]
        );

        if ($alert->wasRecentlyCreated || $alert->wasChanged()) {
            SendGrupoAlertEmailJob::dispatch($alert->id, config('app.url'))->onQueue('high');
            Log::warning("ALERTA {$type}", [
                'cliente' => $customer->customer_number,
                'descricao' => $description
            ]);
        }
    }
}
