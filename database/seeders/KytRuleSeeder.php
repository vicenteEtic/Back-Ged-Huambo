<?php

namespace Database\Seeders;

use App\Models\KYT\KytRule;
use App\Models\KYT\KytRuleProduct;
use Illuminate\Database\Seeder;

class KytRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = $this->rules();

        foreach ($rules as $data) {
            $products = $data['products'] ?? [];
            unset($data['products']);

            $rule = KytRule::create($data);

            foreach ($products as $p) {
                KytRuleProduct::create([
                    'kyt_rule_definition_id' => $rule->id,
                    'product_name' => strtoupper(trim($p['name'])),
                    'type' => $p['type'],
                ]);
            }
        }
    }

    private function rules(): array
    {
        return [

            // ============================================================
            // 1. KYT_HIGH_CAPITAL_INCREASE
            // ============================================================
            [
                'slug' => 'high_capital_increase',
                'name' => 'Aumento abrupto de capital',
                'entity_type' => 'individual',
                'threshold_field' => 'capital',
                'threshold_value' => 0,
                'min_events' => null,
                'max_days' => 90,
                'score_base' => 20,
                'score_increments' => ['above_double_threshold' => 10],
                'severity' => 'Alto',
                'description_template' => "AUMENTO ABRUPTO DE CAPITAL\nCliente: {customer} | Tipo: {entity_type}\n\nVariação de capital detetada entre apólices.\nJanela temporal: {max_days} dias\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Aumento abrupto e injustificado do capital seguro entre apólices, sem alteração no perfil financeiro do cliente.',
                'extra_params' => [
                    'variation_threshold_30d' => 40,
                    'variation_threshold_90d' => 70,
                ],
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL', 'type' => 'relevant'],
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO', 'type' => 'relevant'],
                    ['name' => 'SEGURO BAI VIDA', 'type' => 'relevant'],
                    ['name' => 'SEGURO VIDA-FIXE', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'SEGURO VIDA CRÉDITO', 'type' => 'relevant'],
                    ['name' => 'SEGURO VIDA CRÉDITO (AKZ)', 'type' => 'relevant'],
                    ['name' => 'SEG. VIDA CRÉDITO PENSIONISTA', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'AUTOMOVEL', 'type' => 'excluded'],
                    ['name' => 'AUTOMOVEL CVA', 'type' => 'excluded'],
                    ['name' => 'AUTOMOVEL - AKZ', 'type' => 'excluded'],
                    ['name' => 'AUTOMOVEL CVA - AKZ', 'type' => 'excluded'],
                    ['name' => 'NOSSA AUTO', 'type' => 'excluded'],
                    ['name' => 'SEGURO ESCOLAR', 'type' => 'excluded'],
                    ['name' => 'VIAGEM', 'type' => 'excluded'],
                    ['name' => 'VIAGEM E ASSISTÊNCIA', 'type' => 'excluded'],
                    ['name' => 'VIAGEM E ASSISTÊNCIA AKZ', 'type' => 'excluded'],
                    ['name' => 'ROUBO', 'type' => 'excluded'],
                    ['name' => 'QUEBRA DE VIDROS', 'type' => 'excluded'],
                ],
            ],
            [
                'slug' => 'high_capital_increase',
                'name' => 'Aumento abrupto de capital',
                'entity_type' => 'collective',
                'threshold_field' => 'capital',
                'threshold_value' => 0,
                'min_events' => null,
                'max_days' => 90,
                'score_base' => 20,
                'score_increments' => ['above_double_threshold' => 10],
                'severity' => 'Alto',
                'description_template' => "AUMENTO ABRUPTO DE CAPITAL\nCliente: {customer} | Tipo: {entity_type}\n\nVariação de capital detetada entre apólices.\nJanela temporal: {max_days} dias\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Aumento abrupto e injustificado do capital seguro entre apólices colectivas, sem suporte contabilístico ou expansão empresarial.',
                'extra_params' => [
                    'variation_threshold_30d' => 60,
                    'variation_threshold_90d' => 100,
                ],
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO', 'type' => 'relevant'],
                    ['name' => 'VIDA RISCO GRUPO', 'type' => 'relevant'],
                    ['name' => 'GRUPO-CAPITAL P/ADERENTE', 'type' => 'relevant'],
                    ['name' => 'GRUPO-CAPITAL PESSOAS DIVERSAS', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES BAI', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'MULTI-RISCOS/INDUSTRIA', 'type' => 'excluded'],
                    ['name' => 'INCENDIO/RISCO INDUSTRIAL', 'type' => 'excluded'],
                    ['name' => 'CAUÇÃO', 'type' => 'excluded'],
                    ['name' => 'CONSTRUÇOES', 'type' => 'excluded'],
                    ['name' => 'PETROQUÍMICA', 'type' => 'excluded'],
                    ['name' => 'MINEIRO', 'type' => 'excluded'],
                ],
            ],

            // ============================================================
            // 2. KYT_POLICY_LIFECYCLE_ABUSE
            // ============================================================
            [
                'slug' => 'policy_lifecycle_abuse',
                'name' => 'Abuso do ciclo de vida das apólices',
                'entity_type' => 'individual',
                'threshold_field' => 'premium_total',
                'threshold_value' => 1_000_000.00,
                'min_events' => 2,
                'max_days' => 60,
                'score_base' => 15,
                'score_increments' => ['above_double_threshold' => 5, 'events_above_min' => 5, 'half_window' => 5],
                'severity' => 'Alto',
                'description_template' => "ABUSO DO CICLO DE VIDA DAS APÓLICES\nCliente: {customer} | Tipo: {entity_type}\n\nEventos detetados: {events}\nJanela temporal: {max_days} dias\nPrémio total: {total} (limiar: {threshold})\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Cancelamentos, resgates e substituição rápidos de apólices. Múltiplos resgates ou cancelamentos com substituição reiterados.',
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL', 'type' => 'relevant'],
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO', 'type' => 'relevant'],
                    ['name' => 'SEGURO BAI VIDA', 'type' => 'relevant'],
                    ['name' => 'SEGURO VIDA-FIXE', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'SAUDE INDIVIDUAL VITAL', 'type' => 'excluded'],
                    ['name' => 'SAUDE INDIVIDUAL VITAL LEVE', 'type' => 'excluded'],
                    ['name' => 'ASSISTÊNCIA SAÚDE', 'type' => 'excluded'],
                    ['name' => 'SEGURO ESCOLAR', 'type' => 'excluded'],
                    ['name' => 'AMPARO FAMILIAR', 'type' => 'excluded'],
                ],
            ],
            [
                'slug' => 'policy_lifecycle_abuse',
                'name' => 'Abuso do ciclo de vida das apólices',
                'entity_type' => 'collective',
                'threshold_field' => 'premium_total',
                'threshold_value' => 10_000_000.00,
                'min_events' => 3,
                'max_days' => 90,
                'score_base' => 15,
                'score_increments' => ['above_double_threshold' => 5, 'events_above_min' => 5, 'half_window' => 5],
                'severity' => 'Alto',
                'description_template' => "ABUSO DO CICLO DE VIDA DAS APÓLICES\nCliente: {customer} | Tipo: {entity_type}\n\nEventos detetados: {events}\nJanela temporal: {max_days} dias\nPrémio total: {total} (limiar: {threshold})\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Cancelamentos, resgates e substituição rápidos de apólices colectivas. Múltiplos resgates ou cancelamentos com substituição reiterados.',
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO', 'type' => 'relevant'],
                    ['name' => 'GRUPO-CAPITAL P/ADERENTE', 'type' => 'relevant'],
                    ['name' => 'GRUPO-CAPITAL PESSOAS DIVERSAS', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES BAI', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'SAUDE GRUPO', 'type' => 'excluded'],
                    ['name' => 'SAUDE GRUPO - RESSEGURO 100%', 'type' => 'excluded'],
                    ['name' => 'AC TRABALHO/TRAB. C/PROPRIA', 'type' => 'excluded'],
                ],
            ],

            // ============================================================
            // 3. KYT_HIGH_PREMIUM_LOW_RISK
            // ============================================================
            [
                'slug' => 'high_premium_low_risk',
                'name' => 'Prémio elevado vs risco segurado',
                'entity_type' => 'individual',
                'threshold_field' => 'premium_total',
                'threshold_value' => 0,
                'min_events' => null,
                'max_days' => null,
                'score_base' => 20,
                'score_increments' => ['above_double_threshold' => 10],
                'severity' => 'Alto',
                'description_template' => "PRÉMIO ELEVADO VS RISCO SEGURADO\nCliente: {customer} | Tipo: {entity_type}\n\nPrémio total detetado: {total}\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Pagamento de prémio elevado incompatível com o risco segurado ou capacidade financeira do cliente.',
                'extra_params' => [
                    'income_ratio_threshold' => 0.10,
                ],
                'products' => [
                    ['name' => 'SEGURO VIDA CRÉDITO', 'type' => 'relevant'],
                    ['name' => 'SEGURO VIDA CRÉDITO (AKZ)', 'type' => 'relevant'],
                    ['name' => 'SEG. VIDA CRÉDITO PENSIONISTA', 'type' => 'relevant'],
                    ['name' => 'SEGURO BAI VIDA', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'SEGURO VIDA-FIXE', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'SEGURO ESCOLAR', 'type' => 'excluded'],
                    ['name' => 'ASSISTÊNCIA SAÚDE', 'type' => 'excluded'],
                    ['name' => 'VIAGEM', 'type' => 'excluded'],
                    ['name' => 'VIAGEM E ASSISTÊNCIA', 'type' => 'excluded'],
                ],
            ],
            [
                'slug' => 'high_premium_low_risk',
                'name' => 'Prémio elevado vs risco segurado',
                'entity_type' => 'collective',
                'threshold_field' => 'premium_total',
                'threshold_value' => 0,
                'min_events' => null,
                'max_days' => null,
                'score_base' => 20,
                'score_increments' => ['above_double_threshold' => 10],
                'severity' => 'Alto',
                'description_template' => "PRÉMIO ELEVADO VS RISCO SEGURADO\nCliente: {customer} | Tipo: {entity_type}\n\nPrémio total detetado: {total}\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Pagamento de prémio elevado incompatível com o risco segurado ou capacidade financeira da empresa.',
                'extra_params' => [
                    'revenue_ratio_threshold' => 0.25,
                ],
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO', 'type' => 'relevant'],
                    ['name' => 'GRUPO-CAPITAL PESSOAS DIVERSAS', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES BAI', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'MULTI-RISCOS/ESTABELECIMENTOS', 'type' => 'excluded'],
                    ['name' => 'CIVIL PROFISSIONAL', 'type' => 'excluded'],
                    ['name' => 'EXPLORACAO INDUSTRIAL', 'type' => 'excluded'],
                ],
            ],

            // ============================================================
            // 4. KYT_MULTIPLE_SHORT_POLICIES
            // ============================================================
            [
                'slug' => 'multiple_short_policies',
                'name' => 'Múltiplas apólices de curta duração',
                'entity_type' => 'individual',
                'threshold_field' => 'premium_total',
                'threshold_value' => 0,
                'min_events' => 3,
                'max_days' => 60,
                'score_base' => 20,
                'score_increments' => ['events_above_min' => 10, 'half_window' => 5],
                'severity' => 'Alto',
                'description_template' => "MÚLTIPLAS APÓLICES DE CURTA DURAÇÃO\nCliente: {customer} | Tipo: {entity_type}\n\nApólices detetadas: {events} (limiar: {min_events})\nJanela temporal: {max_days} dias\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Subscrição de múltiplas apólices de curta duração para fragmentar valores elevados.',
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO', 'type' => 'relevant'],
                    ['name' => 'VIDA RISCO INDIVIDUAL', 'type' => 'relevant'],
                    ['name' => 'VIAGEM', 'type' => 'relevant'],
                    ['name' => 'VIAGEM E ASSISTÊNCIA', 'type' => 'relevant'],
                    ['name' => 'VIAGEM E ASSISTÊNCIA AKZ', 'type' => 'relevant'],
                    ['name' => 'AMPARO FAMILIAR', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'INCENDIO/RISCO INDUSTRIAL', 'type' => 'excluded'],
                    ['name' => 'PETROQUÍMICA', 'type' => 'excluded'],
                    ['name' => 'MINEIRO', 'type' => 'excluded'],
                    ['name' => 'CONSTRUÇOES', 'type' => 'excluded'],
                ],
            ],
            [
                'slug' => 'multiple_short_policies',
                'name' => 'Múltiplas apólices de curta duração',
                'entity_type' => 'collective',
                'threshold_field' => 'premium_total',
                'threshold_value' => 0,
                'min_events' => 5,
                'max_days' => 90,
                'score_base' => 20,
                'score_increments' => ['events_above_min' => 10, 'half_window' => 5],
                'severity' => 'Alto',
                'description_template' => "MÚLTIPLAS APÓLICES DE CURTA DURAÇÃO\nCliente: {customer} | Tipo: {entity_type}\n\nApólices detetadas: {events} (limiar: {min_events})\nJanela temporal: {max_days} dias\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Subscrição de múltiplas apólices colectivas de curta duração para fragmentar valores elevados.',
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO', 'type' => 'relevant'],
                    ['name' => 'VIDA RISCO GRUPO', 'type' => 'relevant'],
                    ['name' => 'GRUPO-CAPITAL PESSOAS DIVERSAS', 'type' => 'relevant'],
                    ['name' => 'AC.PESSOAIS GRUPO', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES BAI', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'MULTI-RISCOS/INDUSTRIA', 'type' => 'excluded'],
                    ['name' => 'EMPRESAS CONSTRUÇAO CIVIL', 'type' => 'excluded'],
                    ['name' => 'EXPLORACAO INDUSTRIAL', 'type' => 'excluded'],
                ],
            ],

            // ============================================================
            // 5. KYT_THIRD_PARTY_PAYMENTS
            // ============================================================
            [
                'slug' => 'third_party_payments',
                'name' => 'Pagamentos de prémios por terceiros',
                'entity_type' => 'individual',
                'threshold_field' => 'premium_total',
                'threshold_value' => 300_000.00,
                'min_events' => null,
                'max_days' => null,
                'score_base' => 20,
                'score_increments' => ['above_double_threshold' => 10, 'has_receipts_third_party' => 10],
                'severity' => 'Alto',
                'description_template' => "PAGAMENTOS DE PRÉMIOS POR TERCEIROS\nCliente: {customer} | Tipo: {entity_type}\n\nPrémio total detetado: {total}\nLimiar aplicado: {threshold}\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Pagamentos de prémios realizados por terceiros sem relação clara com o segurado.',
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL', 'type' => 'relevant'],
                    ['name' => 'SEGURO BAI VIDA', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'SEGURO VIDA-FIXE', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'AUTOMOVEL CVA', 'type' => 'excluded'],
                    ['name' => 'AUTOMOVEL CVA - AKZ', 'type' => 'excluded'],
                    ['name' => 'VIAGEM', 'type' => 'excluded'],
                    ['name' => 'ROUBO', 'type' => 'excluded'],
                ],
            ],
            [
                'slug' => 'third_party_payments',
                'name' => 'Pagamentos de prémios por terceiros',
                'entity_type' => 'collective',
                'threshold_field' => 'premium_total',
                'threshold_value' => 1_000_000.00,
                'min_events' => null,
                'max_days' => null,
                'score_base' => 20,
                'score_increments' => ['above_double_threshold' => 10, 'has_receipts_third_party' => 10],
                'severity' => 'Alto',
                'description_template' => "PAGAMENTOS DE PRÉMIOS POR TERCEIROS\nCliente: {customer} | Tipo: {entity_type}\n\nPrémio total detetado: {total}\nLimiar aplicado: {threshold}\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Pagamentos de prémios realizados por terceiros sem relação clara com a entidade.',
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO', 'type' => 'relevant'],
                    ['name' => 'GRUPO-CAPITAL PESSOAS DIVERSAS', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES BAI', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'SAUDE GRUPO', 'type' => 'excluded'],
                    ['name' => 'AC TRABALHO/TRAB. C/PROPRIA', 'type' => 'excluded'],
                ],
            ],

            // ============================================================
            // 6. KYT_FREQUENT_BENEFICIARY_CHANGES
            // ============================================================
            [
                'slug' => 'frequent_beneficiary_changes',
                'name' => 'Alterações frequentes de beneficiários',
                'entity_type' => 'individual',
                'threshold_field' => null,
                'threshold_value' => null,
                'min_events' => 3,
                'max_days' => 180,
                'score_base' => 20,
                'score_increments' => ['events_above_min' => 10, 'half_window' => 5],
                'severity' => 'Alto',
                'description_template' => "ALTERAÇÕES FREQUENTES DE BENEFICIÁRIOS\nCliente: {customer} | Tipo: {entity_type}\n\nAlterações detetadas: {events}\nJanela temporal: {max_days} dias\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Alterações frequentes de beneficiários sem fundamento económico ou familiar plausível.',
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL', 'type' => 'relevant'],
                    ['name' => 'SEGURO BAI VIDA', 'type' => 'relevant'],
                    ['name' => 'SEGURO VIDA-FIXE', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'AUTOMOVEL', 'type' => 'excluded'],
                    ['name' => 'INCENDIO/RISCO SIMPLES', 'type' => 'excluded'],
                    ['name' => 'EQUIPAMENTO ELECTRONICO', 'type' => 'excluded'],
                ],
            ],
            [
                'slug' => 'frequent_beneficiary_changes',
                'name' => 'Alterações frequentes de beneficiários',
                'entity_type' => 'collective',
                'threshold_field' => null,
                'threshold_value' => null,
                'min_events' => 2,
                'max_days' => 90,
                'score_base' => 20,
                'score_increments' => ['events_above_min' => 10, 'half_window' => 5],
                'severity' => 'Alto',
                'description_template' => "ALTERAÇÕES FREQUENTES DE BENEFICIÁRIOS\nCliente: {customer} | Tipo: {entity_type}\n\nAlterações detetadas: {events}\nJanela temporal: {max_days} dias\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Alterações frequentes de beneficiários sem fundamento económico ou societário plausível.',
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO', 'type' => 'relevant'],
                    ['name' => 'GRUPO-CAPITAL PESSOAS DIVERSAS', 'type' => 'relevant'],
                    ['name' => 'VIDA RISCO GRUPO', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES BAI', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'MULTI-RISCOS/ESTABELECIMENTOS', 'type' => 'excluded'],
                    ['name' => 'CAUÇÃO', 'type' => 'excluded'],
                    ['name' => 'CONSTRUÇOES', 'type' => 'excluded'],
                ],
            ],

            // ============================================================
            // 7. KYT_HIGH_RISK_GEOGRAPHY
            // ============================================================
            [
                'slug' => 'high_risk_geography',
                'name' => 'Alto risco geográfico',
                'entity_type' => 'individual',
                'threshold_field' => 'premium_total',
                'threshold_value' => 250_000.00,
                'min_events' => null,
                'max_days' => null,
                'score_base' => 20,
                'score_increments' => ['above_double_threshold' => 10, 'has_country_origin' => 5],
                'severity' => 'Alto',
                'description_template' => "ALTO RISCO GEOGRÁFICO\nCliente: {customer} | Tipo: {entity_type}\n\nPrémio total detetado: {total}\nLimiar aplicado: {threshold}\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Relações financeiras com jurisdições classificadas como de alto risco pelo GAFI.',
                'products' => [
                    ['name' => 'VIAGEM', 'type' => 'relevant'],
                    ['name' => 'VIAGEM E ASSISTÊNCIA', 'type' => 'relevant'],
                    ['name' => 'VIAGEM E ASSISTÊNCIA AKZ', 'type' => 'relevant'],
                    ['name' => 'EMBARCACOES DE RECREIO', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'SAÚDE MWANGOLÉ', 'type' => 'excluded'],
                    ['name' => 'SEGURO ESCOLAR', 'type' => 'excluded'],
                    ['name' => 'AMPARO FAMILIAR', 'type' => 'excluded'],
                ],
            ],
            [
                'slug' => 'high_risk_geography',
                'name' => 'Alto risco geográfico',
                'entity_type' => 'collective',
                'threshold_field' => 'premium_total',
                'threshold_value' => 1_500_000.00,
                'min_events' => null,
                'max_days' => null,
                'score_base' => 20,
                'score_increments' => ['above_double_threshold' => 10, 'has_country_origin' => 5],
                'severity' => 'Alto',
                'description_template' => "ALTO RISCO GEOGRÁFICO\nCliente: {customer} | Tipo: {entity_type}\n\nPrémio total detetado: {total}\nLimiar aplicado: {threshold}\nApólices envolvidas:\n{products}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Relações financeiras com jurisdições classificadas como de alto risco pelo GAFI.',
                'products' => [
                    ['name' => 'MERCADORIA TRANSPORTADAS/MARITIMO', 'type' => 'relevant'],
                    ['name' => 'MERCADORIA TRANSPORTADAS/RODOVIÁRIO', 'type' => 'relevant'],
                    ['name' => 'MERCADORIA TRANSPORTADAS/FERROVIÁRIO', 'type' => 'relevant'],
                    ['name' => 'MERCADORIA TRANSPORTADAS/AEREO', 'type' => 'relevant'],
                    ['name' => 'CASCO', 'type' => 'relevant'],
                    ['name' => 'EMBARCACOES DE RECREIO', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES BAI', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'MULTI-RISCOS/HABITACAO', 'type' => 'excluded'],
                    ['name' => 'MRH BANCA', 'type' => 'excluded'],
                ],
            ],

            // ============================================================
            // 8. KYT_OVERPAYMENT_REFUND
            // ============================================================
            [
                'slug' => 'overpayment_refund',
                'name' => 'Sobrepagamento de prémio com reembolso',
                'entity_type' => 'individual',
                'threshold_field' => 'premium_total',
                'threshold_value' => 0,
                'min_events' => null,
                'max_days' => null,
                'score_base' => 20,
                'score_increments' => ['ratio_above_20pct' => 10],
                'severity' => 'Médio',
                'description_template' => "SOBREPAGAMENTO COM REEMBOLSO - {entity_type}\nProduto: {total}\nApólices: {products}\nPrémio total: {total}\nValor total reembolsado: {threshold}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Sobrepagamento de prémio seguido de pedido de reembolso, compatível com esquemas de movimentação indireta de valores.',
                'extra_params' => [
                    'overpayment_ratio_min' => 0.05,
                ],
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL', 'type' => 'relevant'],
                    ['name' => 'SEGURO BAI VIDA', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'SEGURO VIDA-FIXE', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'AUTOMOVEL', 'type' => 'excluded'],
                    ['name' => 'ROUBO', 'type' => 'excluded'],
                    ['name' => 'PROTECÇÃO CONTRA ASSALTOS', 'type' => 'excluded'],
                ],
            ],
            [
                'slug' => 'overpayment_refund',
                'name' => 'Sobrepagamento de prémio com reembolso',
                'entity_type' => 'collective',
                'threshold_field' => 'premium_total',
                'threshold_value' => 0,
                'min_events' => null,
                'max_days' => null,
                'score_base' => 20,
                'score_increments' => ['ratio_above_20pct' => 10],
                'severity' => 'Médio',
                'description_template' => "SOBREPAGAMENTO COM REEMBOLSO - {entity_type}\nProduto: {total}\nApólices: {products}\nPrémio total: {total}\nValor total reembolsado: {threshold}\n\nInterpretação AML:\n{interpretation}",
                'interpretation_aml' => 'Sobrepagamento de prémio seguido de pedido de reembolso, compatível com esquemas de movimentação indireta de valores.',
                'extra_params' => [
                    'overpayment_ratio_min' => 0.05,
                ],
                'products' => [
                    ['name' => 'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO FIXO', 'type' => 'relevant'],
                    ['name' => 'PRÉMIO VARIÁVEL', 'type' => 'relevant'],
                    ['name' => 'GRUPO-CAPITAL PESSOAS DIVERSAS', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES BAI', 'type' => 'relevant'],
                    ['name' => 'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA', 'type' => 'relevant'],
                    ['name' => 'SAUDE GRUPO', 'type' => 'excluded'],
                    ['name' => 'MULTI-RISCOS/INDUSTRIA', 'type' => 'excluded'],
                    ['name' => 'CONSTRUÇOES', 'type' => 'excluded'],
                ],
            ],
        ];
    }
}
