<?php

namespace App\Services\KYT\Rules;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;
use App\Services\KYT\Rules\Contracts\RuleHandler;
use Illuminate\Support\Carbon;

class DefaultRuleHandler implements RuleHandler
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

        $relevant = $rule->relevantProducts();
        $excluded = $rule->excludedProducts();

        $filtered = $this->filterProducts($policies, $relevant, $excluded);
        if (empty($filtered)) return [];

        $thresholdField = $rule->threshold_field ?? 'premium_total';
        $filtered = $this->filterZeroValues($filtered, $thresholdField);
        if (empty($filtered)) return [];

        $totalValue = array_sum(array_column($filtered, $thresholdField));

        if ($totalValue < ($rule->threshold_value ?? 0)) return [];

        $entityLabel = $this->entityLabel($customer);

        $score = $rule->score_base;
        $description = $this->buildDescription($rule, $customer, $entityLabel, $totalValue, $filtered);

        return [[
            'name' => $rule->name,
            'description' => $description,
            'severity' => $rule->severity ?? 'Alto',
            'score' => $score,
        ]];
    }

    protected function filterZeroValues(array $policies, string $field): array
    {
        return array_values(array_filter($policies, fn($p) => ((float)($p[$field] ?? 0)) > 0));
    }

    protected function filterProducts(array $policies, array $relevant, array $excluded): array
    {
        $result = [];
        foreach ($policies as $p) {
            $p = is_object($p) ? (array) $p : $p;
            $product = strtoupper(trim($p['descricao_produto'] ?? ''));
            if (!empty($relevant) && !in_array($product, $relevant)) continue;
            if (!empty($excluded) && in_array($product, $excluded)) continue;
            $result[] = $p;
        }
        return $result;
    }

    protected function entityLabel(Entities $customer): string
    {
        return ((int)($customer->entity_type ?? 0)) === \App\Enum\TypeEntity::COLECTIVA->value
            ? 'Coletiva'
            : 'Singular';
    }

    protected function buildDescription(
        KytRule $rule,
        Entities $customer,
        string $entityLabel,
        float $totalValue,
        array $filtered
    ): string
    {
        $policyList = $this->formatPolicyList($filtered);

        $replacements = [
            '{customer}' => $customer->customer_number,
            '{entity_type}' => $entityLabel,
            '{threshold}' => $this->formatMoney($rule->threshold_value ?? 0),
            '{total}' => $this->formatMoney($totalValue),
            '{threshold_field}' => $rule->threshold_field ?? 'premium_total',
            '{products}' => $policyList,
            '{events}' => (string)count($filtered),
            '{min_events}' => (string)($rule->min_events ?? 0),
            '{window_days}' => '',
            '{max_days}' => (string)($rule->max_days ?? 0),
            '{interpretation}' => $rule->interpretation_aml,
        ];

        return strtr($rule->description_template, $replacements);
    }

    protected function formatMoney($value): string
    {
        return number_format((float)$value, 2, ',', ' ') . ' Kz';
    }

    protected function formatPolicyList(array $policies): string
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

    protected function normalize(array $data): array
    {
        return array_map(fn($v) => is_object($v) ? (array) $v : $v, $data);
    }
}
