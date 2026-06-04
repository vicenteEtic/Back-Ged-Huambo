<?php

namespace App\Services\KYT\Rules;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;
use App\Services\KYT\Rules\Contracts\RuleHandler;
use Illuminate\Support\Carbon;

class MultipleShortPoliciesHandler implements RuleHandler
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

        $filtered = array_values(array_filter($policies, function ($p) use ($relevant, $excluded) {
            $product = strtoupper(trim($p['descricao_produto'] ?? ''));
            if (!empty($relevant) && !in_array($product, $relevant)) return false;
            if (!empty($excluded) && in_array($product, $excluded)) return false;
            return ($p['premium_total'] ?? 0) > 0;
        }));

        $minEvents = $rule->min_events ?? 3;
        $maxDays = $rule->max_days ?? 60;

        if (count($filtered) < $minEvents) return [];

        usort($filtered, fn($a, $b) => ($a['data_inicio'] ?? '') <=> ($b['data_inicio'] ?? ''));

        $windowStart = $this->safeDate($filtered[0]['data_inicio'] ?? null);
        $windowEnd = $this->safeDate($filtered[count($filtered) - 1]['data_inicio'] ?? null);
        if (!$windowStart || !$windowEnd) return [];

        $windowDays = $windowStart->diffInDays($windowEnd);
        if ($windowDays > $maxDays) return [];

        $totalPremium = array_sum(array_column($filtered, 'premium_total'));
        $entityLabel = $this->entityLabel($customer);

        $score = $rule->score_base;
        if (count($filtered) >= $minEvents + 2) {
            $score += ($rule->score_increments['events_above_min'] ?? 10);
        }
        if ($windowDays <= $maxDays / 2) {
            $score += ($rule->score_increments['half_window'] ?? 5);
        }

        $policyList = $this->formatPolicyList($filtered);

        $description = strtr($rule->description_template, [
            '{customer}' => $customer->customer_number,
            '{entity_type}' => $entityLabel,
            '{events}' => count($filtered),
            '{min_events}' => $minEvents,
            '{window_days}' => $windowDays,
            '{max_days}' => $maxDays,
            '{total}' => $this->formatMoney($totalPremium),
            '{threshold}' => $this->formatMoney($rule->threshold_value ?? 0),
            '{products}' => $policyList,
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

    private function safeDate($date): ?Carbon
    {
        try {
            if (!$date) return null;
            if ($date === '0000-00-00' || $date === '1900-01-01') return null;
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
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
