<?php

namespace App\Services\KYT\Rules;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;
use App\Services\KYT\Rules\Contracts\RuleHandler;

class ThirdPartyPaymentHandler implements RuleHandler
{
    public function check(
        Entities $customer,
        KytRule $rule,
        array $policies,
        array $changes = [],
        array $refunds = [],
        array $receipts = [],
        array $beneficiaries = []
    ): array
    {
        $policies = $this->normalize($policies);
        $receipts = $this->normalize($receipts);

        $relevant = $rule->relevantProducts();
        $excluded = $rule->excludedProducts();

        $filtered = array_values(array_filter($policies, function ($p) use ($relevant, $excluded) {
            $product = strtoupper(trim($p['descricao_produto'] ?? ''));
            if (!empty($relevant) && !in_array($product, $relevant)) return false;
            if (!empty($excluded) && in_array($product, $excluded)) return false;
            return ($p['premium_total'] ?? 0) > 0;
        }));

        if (empty($filtered)) return [];

        $relevantPolicyNums = array_map(fn($p) => $p['numero_apolice'], $filtered);

        $thirdPartyReceipts = [];
        foreach ($receipts as $r) {
            $polNum = $r['numero_apolice'] ?? null;
            if (!$polNum || !in_array($polNum, $relevantPolicyNums)) continue;

            $indicator = strtolower(trim($r['indicador_pagamento_terceiro'] ?? ''));
            $payerName = trim($r['nome_pagador'] ?? '');
            $payerNif = trim($r['nif_pagador'] ?? '');

            if ($indicator === 'sim' && $payerName) {
                $thirdPartyReceipts[$polNum][] = [
                    'payer' => $payerName,
                    'nif' => $payerNif,
                ];
            }
        }

        $totalPremium = array_sum(array_column($filtered, 'premium_total'));
        $threshold = $rule->threshold_value ?? 0;

        if ($totalPremium < $threshold && empty($thirdPartyReceipts)) return [];

        $entityLabel = $this->entityLabel($customer);

        $payerLines = [];
        foreach ($thirdPartyReceipts as $polNum => $payers) {
            $uniquePayers = array_unique(array_map(fn($t) => $t['payer'], $payers));
            $nifs = array_unique(array_filter(array_map(fn($t) => $t['nif'], $payers)));
            $line = $polNum . ' → ' . implode(', ', $uniquePayers);
            if (!empty($nifs)) {
                $line .= ' (NIF: ' . implode(', ', $nifs) . ')';
            }
            $payerLines[] = $line;
        }

        $payerDetails = '';
        if (!empty($payerLines)) {
            $payerDetails = "\nPagadores identificados:\n" . implode("\n", $payerLines);
        }

        $policyList = $this->formatPolicyList($filtered);

        $score = $rule->score_base;
        if ($totalPremium >= $threshold * 2) {
            $score += ($rule->score_increments['above_double_threshold'] ?? 5);
        }

        $description = strtr($rule->description_template, [
            '{customer}' => $customer->customer_number,
            '{entity_type}' => $entityLabel,
            '{total}' => $this->formatMoney($totalPremium),
            '{threshold}' => $this->formatMoney($threshold),
            '{products}' => $policyList,
            '{payer_details}' => $payerDetails,
            '{interpretation}' => $rule->interpretation_aml,
        ]);

        return [[
            'name' => $rule->name,
            'description' => $description,
            'severity' => $rule->severity ?? 'Alto',
            'score' => $score,
        ]];
    }

    private function entityLabel(Entities $customer): string
    {
        return ((int)($customer->entity_type ?? 0)) === \App\Enum\TypeEntity::COLECTIVA->value
            ? 'Coletiva'
            : 'Singular';
    }

    private function formatMoney($value): string
    {
        return number_format((float)$value, 2, ',', ' ') . ' Kz';
    }

    private function formatPolicyList(array $policies): string
    {
        $lines = [];
        foreach ($policies as $p) {
            $num = $p['numero_apolice'] ?? 'N/A';
            $prod = $p['descricao_produto'] ?? 'N/A';
            $prem = $this->formatMoney($p['premium_total'] ?? 0);
            $lines[] = "{$num} ({$prod}) - {$prem}";
        }
        return implode("\n", $lines);
    }

    private function normalize(array $data): array
    {
        return array_map(fn($v) => is_object($v) ? (array) $v : $v, $data);
    }
}
