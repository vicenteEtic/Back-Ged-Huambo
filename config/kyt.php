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
        'high_risk_geography'              => \App\Services\KYT\Rules\HighRiskGeographyHandler::class,
        'multiple_cancellations_rescues' => \App\Services\KYT\Rules\PolicyLifecycleAbuseHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Nomes Fixos dos Cenários (usado no alert.type)
    |--------------------------------------------------------------------------
    |
    | Estes nomes são fixos em código e não mudam quando o utilizador
    | edita o nome da regra na BD. Garantem consistência do alert.type.
    |
    */
    'scenario_names' => [
        'high_capital_increase'         => 'Aumento abrupto e injustificado do capital seguro entre apólices',
        'policy_lifecycle_abuse'        => 'Cancelamento, resgate e substituição rápidas de apólices',
        'high_premium_low_risk'         => 'Prémio elevado incompatível com a capacidade financeira do cliente',
        'multiple_short_policies'       => 'Subscrição de múltiplas apólices de curta duração',
        'third_party_payments'          => 'Pagamentos de prémios por terceiros sem relação clara com o segurado',
        'frequent_beneficiary_changes'  => 'Mudanças frequentes de beneficiários sem justificação aparente',
        'high_risk_geography'           => 'Apólices com beneficiários ou pagamentos de jurisdições de alto risco',
        'overpayment_refund'            => 'Sobrepagamento de prémios seguido de pedido de reembolso para terceiros',
        'multiple_cancellations_rescues' => 'Cancelamento, resgate e substituição rápidas de apólices',
    ],
];
