<?php
namespace App\Services\Aml;

use App\Models\Transation\Transation;
use App\Services\Aml\Rules\{
    AmountLimitRule,
    ProductLimitRule,
    FrequencyRule,
    SmurfingRule,
    ProfileDeviationRule,
    HighRiskCountryRule
};

class AmlRuleEngine
{
    protected array $rules;

    public function __construct()
    {
        $this->rules = [
            new AmountLimitRule(),
            new ProductLimitRule(),
            new FrequencyRule(),
            new SmurfingRule(),
            new ProfileDeviationRule(),
            new HighRiskCountryRule(),
        ];
    }

    public function evaluate(array $transaction): array
    {
        $alerts = [];

        foreach ($this->rules as $rule) {
            $result = $rule->apply($transaction);
            if (!empty($result)) {
                $alerts = array_merge($alerts, $result);
            }
        }

        return $alerts;
    }
}
