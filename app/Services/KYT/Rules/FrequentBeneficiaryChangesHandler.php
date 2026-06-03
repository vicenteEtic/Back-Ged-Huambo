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
        $changes = $this->normalize($changes);
        $beneficiaries = $this->normalize($beneficiaries);

        $relevant = $rule->relevantProducts();
        $excluded = $rule->excludedProducts();

        $relevantPolicyNums = $this->collectPolicyNums($policies, $relevant, $excluded);
        if (empty($relevantPolicyNums)) return [];

        $minChanges = $rule->min_events ?? 3;
        $maxDays = $rule->max_days ?? 180;

        if (count($changes) < $minChanges) return [];

        $justifiedMotives = ['herança', 'casamento', 'divórcio', 'nascimento', 'óbito', 'falecimento', 'alteração familiar'];

        $beneficiaryMap = [];
        foreach ($beneficiaries as $b) {
            $bPolNum = $b['numero_apolice'] ?? null;
            $bName = $b['nome_beneficiario'] ?? '';
            if ($bPolNum && $bName) {
                $beneficiaryMap[$bPolNum][] = $bName;
            }
        }

        $beneficiaryChanges = [];
        foreach ($changes as $change) {
            $polNum = $change['numero_apolice'] ?? null;
            if (!$polNum || !in_array($polNum, $relevantPolicyNums)) continue;

            $changeDate = $this->safeDate($change['data_alteracao'] ?? $change['created_at'] ?? null);
            if (!$changeDate) continue;

            $motive = strtolower(trim($change['motivo_alteracao'] ?? ''));
            if (in_array($motive, $justifiedMotives)) continue;

            $product = '';
            foreach ($policies as $p) {
                if ($p['numero_apolice'] === $polNum) {
                    $product = $p['descricao_produto'];
                    break;
                }
            }

            $bNames = array_unique($beneficiaryMap[$polNum] ?? []);

            if (empty($bNames)) {
                $extracted = $this->extractBeneficiaryNames($change['motivo_alteracao'] ?? '');
                if (!empty($extracted)) {
                    $bNames = $extracted;
                }
            }

            $beneficiaryChanges[] = [
                'date' => $changeDate,
                'polNum' => $polNum,
                'product' => $product,
                'beneficiaries' => $bNames,
            ];
        }

        if (count($beneficiaryChanges) < $minChanges) return [];

        usort($beneficiaryChanges, fn($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

        $firstDate = $beneficiaryChanges[0]['date'];
        $lastDate = $beneficiaryChanges[count($beneficiaryChanges) - 1]['date'];
        $windowDays = $firstDate->diffInDays($lastDate);

        if ($windowDays > $maxDays) return [];

        $entityLabel = $this->entityLabel($customer);

        $polDetails = [];
        foreach ($beneficiaryChanges as $c) {
            $detail = $c['polNum'] . ' (' . $c['product'] . ')';
            if (!empty($c['beneficiaries'])) {
                $detail .= ' [Beneficiários: ' . implode(', ', $c['beneficiaries']) . ']';
            }
            $polDetails[] = $detail;
        }
        $polDetails = array_unique($polDetails);

        $score = $rule->score_base;
        if (count($beneficiaryChanges) >= $minChanges + 1) {
            $score += ($rule->score_increments['events_above_min'] ?? 10);
        }
        if ($windowDays <= $maxDays / 2) {
            $score += ($rule->score_increments['half_window'] ?? 5);
        }

        $description = strtr($rule->description_template, [
            '{customer}' => $customer->customer_number,
            '{entity_type}' => $entityLabel,
            '{events}' => count($beneficiaryChanges),
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

    private function extractBeneficiaryNames(string $motive): array
    {
        if (empty($motive)) return [];
        $upper = strtoupper(trim($motive));

        if (preg_match('/PARA\s+(.+?)$/u', $upper, $m)) {
            $name = trim(preg_replace('/[\.\s]+$/', '', $m[1]));
            return [$name];
        }
        if (preg_match('/NOVO\s+BENEFICI[ÁA]RIO:?\s+(.+?)$/u', $upper, $m)) {
            $name = trim(preg_replace('/[\.\s]+$/', '', $m[1]));
            return [$name];
        }
        if (preg_match('/INCLUS[ÃA]O\s+(?:DE\s+)?BENEFICI[ÁA]RIO:?\s+(.+?)$/u', $upper, $m)) {
            $name = trim(preg_replace('/[\.\s]+$/', '', $m[1]));
            return [$name];
        }
        if (preg_match('/BENEFICI[ÁA]RIO:?\s+(.+?)$/u', $upper, $m)) {
            $name = trim(preg_replace('/[\.\s]+$/', '', $m[1]));
            if (strlen($name) > 3 && !in_array(strtolower($name), ['alterado', 'incluido', 'removido'])) {
                return [$name];
            }
        }
        return [];
    }
}
