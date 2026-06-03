<?php

namespace App\Services\KYT\Rules;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;
use App\Services\KYT\Rules\Contracts\RuleHandler;

class OverpaymentRefundHandler implements RuleHandler
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
        $refunds = $this->normalize($refunds);
        $receipts = $this->normalize($receipts);

        $relevant = $rule->relevantProducts();
        $excluded = $rule->excludedProducts();

        $relevantPolicyNums = $this->collectPolicyNums($policies, $relevant, $excluded);
        if (empty($relevantPolicyNums)) return [];

        $minRatio = $rule->extra_params['overpayment_ratio_min'] ?? 0.05;

        $policyMap = [];
        foreach ($policies as $p) {
            $policyMap[$p['numero_apolice']] = $p;
        }

        $receiptMap = [];
        foreach ($receipts as $r) {
            $rPolNum = $r['numero_apolice'] ?? null;
            $rValor = (float)($r['valor_pago'] ?? 0);
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
            if ($overpaymentRatio < $minRatio) continue;

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

        if (empty($grouped)) return [];

        $results = [];
        foreach ($grouped as $product => $items) {
            $totalPremium = array_sum(array_column($items, 'premium'));
            $totalRefund = array_sum(array_column($items, 'refundAmount'));
            $maxRatio = max(array_column($items, 'ratio'));
            $hasThirdParty = collect($items)->contains('isThirdParty', true);
            $totalActualPaid = array_sum(array_column($items, 'actualPaid'));

            $policyList = implode(', ', array_map(fn($i) => $i['polNum'], $items));

            $entityLabel = $this->entityLabel($customer);

            $score = $rule->score_base;
            $severity = $rule->severity ?? 'Médio';

            if ($maxRatio >= 0.20) {
                $score += ($rule->score_increments['ratio_above_20pct'] ?? 10);
                $severity = 'Alto';
            }
            if ($totalActualPaid > 0 && $totalActualPaid > $totalPremium) {
                $score += 5;
            }

            $actualPaidLine = $totalActualPaid > 0
                ? "\nValor total pago (recibos): " . $this->formatMoney($totalActualPaid)
                : '';

            $description = strtr($rule->description_template, [
                '{customer}' => $customer->customer_number,
                '{entity_type}' => $entityLabel,
                '{total}' => $this->formatMoney($totalPremium),
                '{threshold}' => $this->formatMoney($totalRefund),
                '{products}' => $policyList,
                '{interpretation}' => $rule->interpretation_aml,
                '{events}' => count($items),
                '{min_events}' => '',
                '{window_days}' => '',
                '{max_days}' => '',
            ]);

            $description = str_replace(
                ["\n\n\n", "\n\n"],
                ["\n", "\n"],
                $description
            );

            $ratioLine = sprintf("Rácio máximo reembolso/prémio: %.2f%%", $maxRatio * 100);
            $thirdPartyLine = "Envolve terceiros: " . ($hasThirdParty ? 'Sim' : 'Não');
            $description .= "\n{$ratioLine}\n{$thirdPartyLine}{$actualPaidLine}";

            $results[] = [
                'name' => $rule->name,
                'description' => $description,
                'severity' => $severity,
                'score' => $score,
            ];
        }

        return $results;
    }

    private function collectPolicyNums(array $policies, array $relevant, array $excluded): array
    {
        $nums = [];
        foreach ($policies as $p) {
            $product = strtoupper(trim($p['descricao_produto'] ?? ''));
            if (!empty($relevant) && !in_array($product, $relevant)) continue;
            if (!empty($excluded) && in_array($product, $excluded)) continue;
            $nums[] = $p['numero_apolice'];
        }
        return $nums;
    }

    private function normalize(array $data): array
    {
        return array_map(fn($v) => is_object($v) ? (array) $v : $v, $data);
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
}
