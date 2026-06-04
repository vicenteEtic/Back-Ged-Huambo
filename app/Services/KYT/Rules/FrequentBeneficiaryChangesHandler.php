<?php

namespace App\Services\KYT\Rules;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;
use App\Services\KYT\Rules\Contracts\RuleHandler;
use Illuminate\Support\Carbon;

class FrequentBeneficiaryChangesHandler implements RuleHandler
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
        $beneficiaries = $this->normalize($beneficiaries);

        $relevant = $rule->relevantProducts();
        $excluded = $rule->excludedProducts();

        $relevantPolicyNums = $this->collectPolicyNums($policies, $relevant, $excluded);
        if (empty($relevantPolicyNums)) return [];

        $minChanges = $rule->min_events ?? 3;
        $maxDays = $rule->max_days ?? 180;

        $beneficiaryEvents = $this->buildBeneficiaryEvents($beneficiaries, $relevantPolicyNums, $policies);

        if (count($beneficiaryEvents) < $minChanges) return [];

        usort($beneficiaryEvents, fn($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

        $firstDate = $beneficiaryEvents[0]['date'];
        $lastDate = $beneficiaryEvents[count($beneficiaryEvents) - 1]['date'];
        $windowDays = $firstDate->diffInDays($lastDate);

        if ($windowDays > $maxDays) return [];

        $entityLabel = $this->entityLabel($customer);

        $polDetails = [];
        foreach ($beneficiaryEvents as $e) {
            $detail = $e['polNum'] . ' (' . $e['product'] . ')';
            if (!empty($e['beneficiaries'])) {
                $detail .= ' [Beneficiários: ' . implode(', ', $e['beneficiaries']) . ']';
            }
            $polDetails[] = $detail;
        }
        $polDetails = array_unique($polDetails);

        $score = $rule->score_base;
        if (count($beneficiaryEvents) >= $minChanges + 1) {
            $score += ($rule->score_increments['events_above_min'] ?? 10);
        }
        if ($windowDays <= $maxDays / 2) {
            $score += ($rule->score_increments['half_window'] ?? 5);
        }

        $description = strtr($rule->description_template, [
            '{customer}' => $customer->customer_number,
            '{entity_type}' => $entityLabel,
            '{events}' => count($beneficiaryEvents),
            '{min_events}' => $minChanges,
            '{window_days}' => $windowDays,
            '{max_days}' => $maxDays,
            '{products}' => implode("\n", $polDetails),
            '{interpretation}' => $rule->interpretation_aml,
            '{total}' => '',
            '{threshold}' => '',
        ]);

        return [[
            'name' => $rule->name,
            'description' => $description,
            'severity' => $rule->severity ?? 'Alto',
            'score' => $score,
        ]];
    }

    private function buildBeneficiaryEvents(array $beneficiaries, array $relevantPolicyNums, array $policies): array
    {
        $benefByPolicy = [];
        foreach ($beneficiaries as $b) {
            $polNum = $b['numero_apolice'] ?? null;
            if (!$polNum || !in_array($polNum, $relevantPolicyNums)) continue;

            $updateDate = $b['data_atualizacao_beneficiario'] ?? null;
            if (!$updateDate) continue;

            $parsedDate = $this->safeDate($updateDate);
            if (!$parsedDate) continue;

            $dateKey = $parsedDate->format('Y-m-d');
            $name = $b['nome_beneficiario'] ?? '';

            $benefByPolicy[$polNum][$dateKey]['date'] = $parsedDate;
            $benefByPolicy[$polNum][$dateKey]['names'][$name] = true;
        }

        $events = [];
        foreach ($benefByPolicy as $polNum => $dates) {
            $product = '';
            foreach ($policies as $p) {
                if ($p['numero_apolice'] === $polNum) {
                    $product = $p['descricao_produto'];
                    break;
                }
            }

            foreach ($dates as $dateKey => $batch) {
                $events[] = [
                    'date' => $batch['date'],
                    'polNum' => $polNum,
                    'product' => $product,
                    'beneficiaries' => array_keys($batch['names']),
                ];
            }
        }

        return $events;
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

}
