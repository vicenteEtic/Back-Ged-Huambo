<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mapeamento de Handlers por Slug
    |--------------------------------------------------------------------------
    |
    | Regras com lógica específica registam aqui o seu handler.
    | As restantes usam DefaultRuleHandler.
    |
    */
    'handlers' => [
        'frequent_beneficiary_changes' => \App\Services\KYT\Rules\FrequentBeneficiaryChangesHandler::class,
        'overpayment_refund'           => \App\Services\KYT\Rules\OverpaymentRefundHandler::class,
        'high_capital_increase'        => \App\Services\KYT\Rules\HighCapitalIncreaseHandler::class,
        'policy_lifecycle_abuse'       => \App\Services\KYT\Rules\PolicyLifecycleAbuseHandler::class,
        'third_party_payments'         => \App\Services\KYT\Rules\ThirdPartyPaymentHandler::class,
        'multiple_short_policies'      => \App\Services\KYT\Rules\MultipleShortPoliciesHandler::class,
    ],
];
