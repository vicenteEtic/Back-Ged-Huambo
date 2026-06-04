<?php

namespace App\Services\KYT\Rules;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;
use App\Services\KYT\Rules\Contracts\RuleHandler;
use Illuminate\Support\Carbon;

class HighCapitalIncreaseHandler implements RuleHandler
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
        $changes = $this->normalize($changes);

        $relevant = $rule->relevantProducts();
        $excluded = $rule->excludedProducts();

        $extra = $rule->extra_params ?? [];
        $threshold30 = ($extra['variation_threshold_30d'] ?? 40) / 100;
        $threshold90 = ($extra['variation_threshold_90d'] ?? 70) / 100;

        $justifiedMotives = ['herança', 'mudança de emprego', 'promoção', 'evento económico'];
        foreach ($changes as $change) {
            $motivo = strtolower(trim($change['motivo_alteracao'] ?? ''));
            if (in_array($motivo, $justifiedMotives)) {
                return [];
            }
        }

        $grouped = [];
        foreach ($policies as $p) {
            $product = strtoupper(trim($p['descricao_produto'] ?? ''));
            if (!empty($relevant) && !in_array($product, $relevant)) continue;
            if (!empty($excluded) && in_array($product, $excluded)) continue;
            if (($p['capital'] ?? 0) <= 0) continue;
            $grouped[$product][] = $p;
        }

        $results = [];
        foreach ($grouped as $product => $group) {
            if (count($group) < 2) continue;

            usort($group, fn($a, $b) => ($a['data_inicio'] ?? '') <=> ($b['data_inicio'] ?? ''));

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

            $entityLabel = $this->entityLabel($customer);

            $policiesList = "Produto: {$product}\n" . implode("\n", array_map(fn($pair) => sprintf(
                "  - %s | Capital: %s | +%.2f%% em %d dias",
                $pair['policy'],
                $this->formatMoney($pair['capital']),
                $pair['increase'],
                $pair['days']
            ), $pairs));

            $score = $rule->score_base;
            $hasHighIncrease = collect($pairs)->contains(fn($p) => $p['increase'] >= 100);
            if ($hasHighIncrease) {
                $score += ($rule->score_increments['above_double_threshold'] ?? 10);
            }

            $description = strtr($rule->description_template, [
                '{customer}' => $customer->customer_number,
                '{entity_type}' => $entityLabel,
                '{products}' => $policiesList,
                '{interpretation}' => $rule->interpretation_aml,
                '{total}' => $this->formatMoney($firstCapital),
                '{events}' => count($pairs),
                '{min_events}' => '',
                '{window_days}' => '',
                '{max_days}' => $rule->max_days ?? 90,
                '{threshold}' => '',
            ]);

            $description .= "\n\nApólice de referência:\n  N.º: {$first['numero_apolice']} | Capital: {$this->formatMoney($firstCapital)} | Início: {$first['data_inicio']}";

            $results[] = [
                'name' => $rule->name,
                'description' => $description,
                'severity' => $rule->severity ?? 'Alto',
                'score' => $score,
            ];
        }

        return $results ?: [];
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
