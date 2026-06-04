<?php

namespace App\Services\KYT\Rules;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;
use App\Services\KYT\Rules\Contracts\RuleHandler;
use Illuminate\Support\Facades\Cache;

class HighRiskGeographyHandler implements RuleHandler
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
        $beneficiaries = $this->normalize($beneficiaries);

        $relevant = $rule->relevantProducts();
        $excluded = $rule->excludedProducts();

        $relevantPolicyNums = $this->collectPolicyNums($policies, $relevant, $excluded);
        if (empty($relevantPolicyNums)) return [];

        $thresholdValue = $rule->threshold_value ?? 250000;
        $minEvents = $rule->min_events ?? 0;

        $highRiskCountries = $this->loadHighRiskCountries();

        $results = [];
        foreach ($relevantPolicyNums as $polNum) {
            $policy = $this->findPolicy($policies, $polNum);
            if (!$policy) continue;

            $premium = (float)($policy['premium_total'] ?? 0);
            if ($premium <= 0) continue;

            $countriesDetected = $this->detectCountries($polNum, $receipts, $beneficiaries);

            if (empty($countriesDetected)) continue;

            $riskCountries = array_intersect($countriesDetected, $highRiskCountries);
            if (empty($riskCountries)) continue;

            if ($minEvents > 0) {
                static $eventCount = [];
                $customerKey = $customer->id;
                $eventCount[$customerKey] = ($eventCount[$customerKey] ?? 0) + 1;
                if ($eventCount[$customerKey] < $minEvents) continue;
            }

            $totalPremium = $this->sumPremium($relevantPolicyNums, $policies);
            if ($totalPremium < $thresholdValue) continue;

            $entityLabel = $this->entityLabel($customer);

            $score = $rule->score_base;

            $description = strtr($rule->description_template, [
                '{customer}' => $customer->customer_number,
                '{entity_type}' => $entityLabel,
                '{total}' => $this->formatMoney($totalPremium),
                '{threshold}' => $this->formatMoney($thresholdValue),
                '{products}' => $this->formatPolicyList($policies, $relevantPolicyNums),
                '{interpretation}' => $rule->interpretation_aml,
                '{events}' => (string)count($relevantPolicyNums),
                '{min_events}' => (string)$minEvents,
                '{window_days}' => '',
                '{max_days}' => '',
                '{payer_details}' => "\nPaíses detetados: " . implode(', ', $countriesDetected)
                    . "\nPaíses de alto risco: " . implode(', ', $riskCountries),
            ]);

            $results[] = [
                'name' => $rule->name,
                'description' => $description,
                'severity' => $rule->severity ?? 'Alto',
                'score' => $score,
            ];
        }

        return $results;
    }

    private function loadHighRiskCountries(): array
    {
        if (!class_exists(\App\Models\Indicator\IndicatorType::class)) {
            return $this->defaultHighRiskCountries();
        }

        return Cache::remember('kyt_high_risk_countries', 3600, function () {
            try {
                $countries = \App\Models\Indicator\IndicatorType::where('indicator_id', 9)
                    ->where('score', '>=', 3)
                    ->pluck('description')
                    ->map(fn($c) => strtoupper(trim($c)))
                    ->filter()
                    ->values()
                    ->toArray();

                if (!empty($countries)) return $countries;
            } catch (\Exception $e) {}

            return $this->defaultHighRiskCountries();
        });
    }

    private function defaultHighRiskCountries(): array
    {
        return [
            'AFGHANISTAN', 'ALBANIA', 'ANGOLA', 'BOTSWANA', 'BURKINA FASO',
            'BURUNDI', 'CAMBODIA', 'CAMEROON', 'CAYMAN ISLANDS', 'CENTRAL AFRICAN REPUBLIC',
            'CHAD', 'COMOROS', 'CONGO', 'COTE D\'IVOIRE', 'CUBA',
            'DEMOCRATIC REPUBLIC OF THE CONGO', 'DJIBOUTI', 'ECUADOR', 'EGYPT', 'EQUATORIAL GUINEA',
            'ERITREA', 'ESWATINI', 'ETHIOPIA', 'GABON', 'GAMBIA',
            'GHANA', 'GUINEA', 'GUINEA-BISSAU', 'HAITI', 'IRAN',
            'IRAQ', 'KENYA', 'LAOS', 'LEBANON', 'LESOTHO',
            'LIBERIA', 'LIBYA', 'MADAGASCAR', 'MALAWI', 'MALI',
            'MAURITANIA', 'MOZAMBIQUE', 'MYANMAR', 'NAMIBIA', 'NIGER',
            'NIGERIA', 'NORTH KOREA', 'PAKISTAN', 'PAPUA NEW GUINEA',
            'RWANDA', 'SAO TOME AND PRINCIPE', 'SENEGAL', 'SIERRA LEONE',
            'SOMALIA', 'SOUTH SUDAN', 'SRI LANKA', 'SUDAN', 'SYRIA',
            'TANZANIA', 'TOGO', 'UGANDA', 'UKRAINE', 'VENEZUELA',
            'YEMEN', 'ZAMBIA', 'ZIMBABWE',
        ];
    }

    private function detectCountries(string $polNum, array $receipts, array $beneficiaries): array
    {
        $countries = [];

        foreach ($receipts as $r) {
            if (($r['numero_apolice'] ?? null) !== $polNum) continue;

            $pais = $this->normalizeCountry($r['pais_iban_origem'] ?? null);
            if ($pais) $countries[] = $pais;

            if (!$pais && !empty($r['iban_origem'])) {
                $extracted = $this->extractCountryFromIBAN($r['iban_origem']);
                if ($extracted) $countries[] = $extracted;
            }
        }

        foreach ($beneficiaries as $b) {
            if (($b['numero_apolice'] ?? null) !== $polNum) continue;

            $pais = $this->normalizeCountry($b['pais_residencia_beneficiario'] ?? null);
            if ($pais) $countries[] = $pais;
        }

        return array_unique(array_filter($countries));
    }

    private function extractCountryFromIBAN(string $iban): ?string
    {
        $iban = strtoupper(trim($iban));
        if (strlen($iban) < 2) return null;
        return substr($iban, 0, 2);
    }

    private function normalizeCountry(?string $country): ?string
    {
        if (!$country) return null;
        $normalized = strtoupper(trim($country));
        return $normalized !== '' ? $normalized : null;
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

    private function findPolicy(array $policies, string $polNum): ?array
    {
        foreach ($policies as $p) {
            if (($p['numero_apolice'] ?? null) === $polNum) return $p;
        }
        return null;
    }

    private function sumPremium(array $policyNums, array $policies): float
    {
        $total = 0;
        foreach ($policies as $p) {
            if (in_array($p['numero_apolice'] ?? null, $policyNums)) {
                $total += (float)($p['premium_total'] ?? 0);
            }
        }
        return $total;
    }

    private function formatPolicyList(array $policies, array $policyNums): string
    {
        $lines = [];
        foreach ($policies as $p) {
            if (!in_array($p['numero_apolice'] ?? null, $policyNums)) continue;
            $lines[] = ($p['numero_apolice'] ?? 'N/A') . ' (' . ($p['descricao_produto'] ?? 'N/A') . ') - ' . $this->formatMoney($p['premium_total'] ?? 0);
        }
        return implode("\n", $lines);
    }

    private function formatMoney($value): string
    {
        return number_format((float)$value, 2, ',', ' ') . ' Kz';
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
