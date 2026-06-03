<?php

namespace App\Services\KYT\Rules;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;
use App\Services\KYT\Rules\Contracts\RuleHandler;
use Illuminate\Support\Carbon;

class PolicyLifecycleAbuseHandler implements RuleHandler
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

        $relevant = $rule->relevantProducts();
        $excluded = $rule->excludedProducts();

        $minEvents = $rule->min_events ?? 2;
        $maxDays = $rule->max_days ?? 60;
        $minPremium = $rule->threshold_value ?? 1000000.00;

        $filtered = array_values(array_filter($policies, function ($p) use ($relevant, $excluded) {
            $product = strtoupper(trim($p['descricao_produto'] ?? ''));
            if (!empty($relevant) && !in_array($product, $relevant)) return false;
            if (!empty($excluded) && in_array($product, $excluded)) return false;
            return ($p['premium_total'] ?? 0) > 0;
        }));

        if (count($filtered) < $minEvents) return [];

        usort($filtered, fn($a, $b) => ($a['data_inicio'] ?? '') <=> ($b['data_inicio'] ?? ''));

        $events = [];
        foreach ($filtered as $p) {
            $inicio = $this->safeDate($p['data_inicio'] ?? null);
            $fim = $this->safeDate($p['data_fim'] ?? null);
            if (!$inicio || !$fim) continue;

            $estado = $p['estado_apolice'] ?? '';
            $isCancelled = in_array(strtolower($estado), ['cancelled', 'terminated', 'anulada']);

            $temResgate = false;
            foreach ($refunds as $r) {
                if (($r['Numero_Apolice'] ?? $r['numero_apolice'] ?? null) === $p['numero_apolice']) {
                    $temResgate = true;
                    break;
                }
            }

            if (!$isCancelled && !$temResgate) continue;
            $events[] = $p;
        }

        if (count($events) < $minEvents) return [];

        $windowStart = $this->safeDate($events[0]['data_inicio']);
        $windowEnd = $this->safeDate($events[count($events) - 1]['data_inicio']);
        if (!$windowStart || !$windowEnd) return [];

        $windowDays = $windowStart->diffInDays($windowEnd);
        if ($windowDays > $maxDays) return [];

        $totalPremium = array_sum(array_column($events, 'premium_total'));
        if ($totalPremium < $minPremium) return [];

        $entityLabel = $this->entityLabel($customer);

        $score = $rule->score_base;
        if ($totalPremium >= $minPremium * 2) {
            $score += ($rule->score_increments['above_double_threshold'] ?? 5);
        }
        if (count($events) >= $minEvents + 1) {
            $score += ($rule->score_increments['events_above_min'] ?? 5);
        }
        if ($windowDays <= $maxDays / 2) {
            $score += ($rule->score_increments['half_window'] ?? 5);
        }

        $policyList = $this->formatPolicyList($events);

        $description = strtr($rule->description_template, [
            '{customer}' => $customer->customer_number,
            '{entity_type}' => $entityLabel,
            '{events}' => count($events),
            '{min_events}' => $minEvents,
            '{window_days}' => $windowDays,
            '{max_days}' => $maxDays,
            '{total}' => $this->formatMoney($totalPremium),
            '{threshold}' => $this->formatMoney($minPremium),
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
}
