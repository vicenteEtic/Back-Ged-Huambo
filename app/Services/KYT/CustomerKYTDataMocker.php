<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use Carbon\Carbon;

class CustomerKYTDataMocker
{
    /**
     * REGRA 1: checkHighCapitalIncrease
     * Simula um aumento súbito e drástico de capital sem justificativa plausível.
     */
    public static function scenarioHighCapitalIncrease(Entities $customer): array
    {
        $policyNumber = 'POL-CAP-INC-01';
        return [
            'policies' => [
                [
                    'numero_apolice' => $policyNumber,
                    'premium_total' => 150000.00,
                    'capital' => 15000000.00, // Capital atualizado elevado
                    'data_inicio' => now()->subMonths(3)->format('Y-m-d'),
                    'descricao_produto' => 'PATRIMONIAL'
                ]
            ],
            'changes' => [
                [
                    'numero_apolice' => $policyNumber,
                    'tipo_alteracao' => 'AUMENTO DE CAPITAL',
                    'valor_anterior' => 2000000.00, // De 2M para 15M
                    'novo_valor' => 15000000.00,    // Variação > 10% e diferença > 1M Kz
                    'motivo_alteracao' => 'Sem Justificação Comercial'
                ]
            ],
            'refunds' => [],
            'receipts' => []
        ];
    }

    /**
     * REGRA 2: checkEarlyRedemption
     * Simula o resgate ou cancelamento antecipado de uma apólice logo após o pagamento do prémio.
     */
    public static function scenarioEarlyRedemption(Entities $customer): array
    {
        $policyNumber = 'POL-EARLY-02';
        return [
            'policies' => [
                [
                    'numero_apolice' => $policyNumber,
                    'premium_total' => 800000.00,
                    'capital' => 20000000.00,
                    'data_inicio' => now()->subDays(15)->format('Y-m-d'), // Subscrita há 15 dias
                    'status' => 'Anulada', // Estado que aciona o gatilho de cancelamento
                    'descricao_produto' => 'VIDA INVESTMENT'
                ]
            ],
            'refunds' => [
                [
                    'Numero_Apolice' => $policyNumber,
                    'Valor_Estorno' => 750000.00, // Reembolso de valor quase integral
                    'Data_Estorno' => now()->subDays(2)->format('Y-m-d'), // < 30 ou 60 dias do início
                    'Nome_Beneficiario' => $customer->social_denomination
                ]
            ],
            'changes' => [],
            'receipts' => []
        ];
    }

    /**
     * REGRA 3: checkHighPremium
     * Simula uma apólice cujo valor do prémio é desproporcionalmente alto face ao capital segurado (>= 8%).
     */
    public static function scenarioHighPremium(Entities $customer): array
    {
        $policyNumber = 'POL-HIGH-PREM-03';
        return [
            'policies' => [
                [
                    'numero_apolice' => $policyNumber,
                    'premium_total' => 500000.00, // Prémio muito alto
                    'capital' => 4000000.00,       // Rácio: 500k / 4M = 12.5% (Garante o gatilho >= 8%)
                    'data_inicio' => now()->format('Y-m-d'),
                    'descricao_produto' => 'RISCO FINANCEIRO'
                ]
            ],
            'changes' => [],
            'refunds' => [],
            'receipts' => []
        ];
    }

    /**
     * REGRA 4: checkMultipleShortPolicies (Smurfing / Fracionamento)
     * Simula o cliente a subscrever múltiplas apólices consecutivas num curto espaço de tempo.
     */
    public static function scenarioSmurfing(Entities $customer): array
    {
        $policies = [];
        $baseDate = now()->subDays(1);

        for ($i = 1; $i <= 5; $i++) {
            $policies[] = [
                'numero_apolice' => "POL-SMURF-04-{$i}",
                'premium_total' => 600000.00, // > 500.000 Kz para subir score de criticidade
                'capital' => 12000000.00,
                'data_inicio' => $baseDate->addHours(2)->format('Y-m-d H:i:s'), // Concentrado na mesma janela
                'descricao_produto' => 'AUTOMÓVEL'
            ];
        }

        return [
            'policies' => $policies,
            'changes' => [],
            'refunds' => [],
            'receipts' => []
        ];
    }

    /**
     * REGRA 5: checkPolicyChurning
     * Simula a quebra artificial de histórico através do cancelamento sistemático de apólices antigas.
     */
    public static function scenarioPolicyChurning(Entities $customer): array
    {
        return [
            'policies' => [
                [
                    'numero_apolice' => 'POL-CHURN-OLD',
                    'premium_total' => 300000.00,
                    'capital' => 10000000.00,
                    'data_inicio' => now()->subMonths(18)->format('Y-m-d'),
                    'status' => 'Anulada', // Apólice antiga cancelada
                    'descricao_produto' => 'MULTIRISCOS'
                ],
                [
                    'numero_apolice' => 'POL-CHURN-NEW',
                    'premium_total' => 320000.00,
                    'capital' => 10000000.00,
                    'data_inicio' => now()->subDays(5)->format('Y-m-d'), // Nova apólice logo a seguir
                    'status' => 'Em vigor',
                    'descricao_produto' => 'MULTIRISCOS'
                ]
            ],
            'changes' => [],
            'refunds' => [],
            'receipts' => []
        ];
    }

    /**
     * REGRA 6: checkRapidReplacement
     * Simula a substituição relâmpago de uma apólice por outra no espaço de 7 dias (Tática de branqueamento).
     */
    public static function scenarioRapidReplacement(Entities $customer): array
    {
        return [
            'policies' => [
                [
                    'numero_apolice' => 'POL-REPLACE-CANCELLED',
                    'premium_total' => 250000.00,
                    'capital' => 5000000.00,
                    'data_inicio' => now()->subDays(10)->format('Y-m-d'),
                    'status' => 'Cancelada',
                    'data_fim' => now()->subDays(4)->format('Y-m-d'), // Cancelada há 4 dias
                    'descricao_produto' => 'RESPONSABILIDADE CIVIL'
                ],
                [
                    'numero_apolice' => 'POL-REPLACE-ACTIVE',
                    'premium_total' => 280000.00,
                    'capital' => 6000000.00,
                    'data_inicio' => now()->subDays(3)->format('Y-m-d'), // Nova emitida 1 dia depois! (<= 7 dias)
                    'status' => 'Em vigor',
                    'descricao_produto' => 'RESPONSABILIDADE CIVIL'
                ]
            ],
            'changes' => [],
            'refunds' => [],
            'receipts' => []
        ];
    }

    /**
     * REGRA 7: checkThirdPartyPayments
     * Simula o pagamento de prémios efetuado por um terceiro sem relação aparente com o cliente.
     */
    public static function scenarioThirdPartyPayments(Entities $customer): array
    {
        $policyNumber = 'POL-3RD-PARTY-07';
        return [
            'policies' => [
                [
                    'numero_apolice' => $policyNumber,
                    'premium_total' => 150000.00, // Deve ser > 100.000 Kz para ativar a regra
                    'capital' => 8000000.00,
                    'data_inicio' => now()->format('Y-m-d'),
                    'descricao_produto' => 'SAÚDE COLETIVO',
                    // Campo aninhado mapeado nas regras internas do seu serviço:
                    'payer' => [
                        'name' => 'Consultoria Internacional Desconhecida Lda',
                        'relation' => 'third_party', // Relação diferente de "self"
                        'origin' => 'Conta Externa'
                    ]
                ]
            ],
            'changes' => [],
            'refunds' => [],
            'receipts' => []
        ];
    }

    /**
     * REGRA 8: checkFrequentBeneficiaryChanges
     * Simula a alteração constante de beneficiários da apólice num curto espaço de tempo.
     */
    public static function scenarioFrequentBeneficiaryChanges(Entities $customer): array
    {
        $policyNumber = 'POL-BENEF-CHG-08';
        return [
            'policies' => [
                [
                    'numero_apolice' => $policyNumber,
                    'premium_total' => 450000.00,
                    'capital' => 30000000.00,
                    'data_inicio' => now()->subMonths(1)->format('Y-m-d'),
                    'descricao_produto' => 'VIDA INTEIRA'
                ]
            ],
            'changes' => [
                // 3 alterações consecutivas de beneficiários na mesma apólice
                ['numero_apolice' => $policyNumber, 'tipo_alteracao' => 'ALTERAÇÃO DE BENEFICIÁRIO', 'data' => now()->subDays(15)->format('Y-m-d')],
                ['numero_apolice' => $policyNumber, 'tipo_alteracao' => 'ALTERAÇÃO DE BENEFICIÁRIO', 'data' => now()->subDays(10)->format('Y-m-d')],
                ['numero_apolice' => $policyNumber, 'tipo_alteracao' => 'ALTERAÇÃO DE BENEFICIÁRIO', 'data' => now()->subDays(2)->format('Y-m-d')]
            ],
            'refunds' => [],
            'receipts' => []
        ];
    }

    /**
     * REGRA 9: checkHighRiskGeography
     * Simula transações financeiras ligadas a paraísos fiscais ou jurisdições sob monitorização reforçada.
     */
    public static function scenarioHighRiskGeography(Entities $customer): array
    {
        $policyNumber = 'POL-GEO-09';
        return [
            'policies' => [
                [
                    'numero_apolice' => $policyNumber,
                    'premium_total' => 200000.00,
                    'capital' => 5000000.00,
                    'descricao_produto' => 'TRANSPORTE DE MERCADORIAS'
                ]
            ],
            'receipts' => [
                [
                    // Chaves em minúsculas conforme exigido pela função de geografia do seu service
                    'numero_apolice' => $policyNumber,
                    'valor_pago' => 200000.00,
                    'iban_origem' => 'CH9300000000000000000',
                    'pais_iban_origem' => 'SUÍÇA' // Alvo de monitorização financeira
                ]
            ],
            'beneficiaries' => [
                [
                    'numero_apolice' => $policyNumber,
                    'nome_beneficiario' => 'Panama Offshore Logistics',
                    'pais_residencia_beneficiario' => 'PANAMÁ' // Paraíso fiscal (Indicator 9)
                ]
            ],
            'changes' => [],
            'refunds' => []
        ];
    }

    /**
     * REGRA 10: checkOverpaymentRefund
     * Simula um pagamento em excesso intencional, gerando estorno imediato para conta de terceiros.
     */
    public static function scenarioOverpayment(Entities $customer): array
    {
        $policyNumber = 'POL-OVER-10';
        $today = now();

        return [
            'policies' => [
                [
                    'numero_apolice' => $policyNumber,
                    'premium_total' => 100000.00, // Prémio estipulado: 100 mil Kz
                    'capital' => 5000000.00,
                    'descricao_produto' => 'AUTOMÓVEL COLETIVO'
                ]
            ],
            'receipts' => [
                [
                    // ATENÇÃO: Esta regra específica no seu service lê chaves em PascalCase/CamelCase!
                    'Numero_Apolice' => $policyNumber,
                    'Valor_Pago' => 250000.00, // Rácio de 250% (Garante o gatilho >= 150%)
                    'Nome_Pagador' => 'Delfim Neto Lourenço',
                    'Data_Pagamento' => $today->format('Y-m-d')
                ]
            ],
            'refunds' => [
                [
                    'Numero_Apolice' => $policyNumber,
                    'Valor_Estorno' => 150000.00, // Devolução do excedente
                    'Data_Estorno' => $today->addDays(2)->format('Y-m-d'), // Dentro dos 30 dias regulamentares
                    'Nome_Beneficiario' => 'Empresa de Fachada, Lda' // Nome do beneficiário diferente do pagador original!
                ]
            ],
            'changes' => []
        ];
    }
}
