<?php

/*
|--------------------------------------------------------------------------
| Configuração do Módulo de Declarações do Gabinete de RH
|--------------------------------------------------------------------------
|
| Metadados dos campos e mapeamento tipo-de-declaração -> campos.
| Serve de fonte para o formulário dinâmico (frontend), para a validação
| e para a geração do conteúdo/documento.
|
| Os field keys estão em inglês e correspondem a colunas em declaration_requests.
|
*/

return [

    /*
    | Valores por omissão preenchidos automaticamente pelo backend.
    */
    'defaults' => [
        'institution_name' => 'Governo da Província do Huambo',
        'employer_entity' => 'Governo da Província do Huambo',
        'issuing_department' => 'Gabinete de Recursos Humanos',
        'signer_role' => 'O DIRECTOR',
    ],

    /*
    | Campos comuns (full_name, gender, issue_date, declaration_number, signer)
    | são auto-preenchidos pelo backend a partir do funcionário e do sistema.
    | Não devem constar no formulário dinâmico — apenas nos campos específicos de cada tipo.
    */

    /*
    | Campos quase-comuns — usados em muitos tipos, mas não em todos.
    */
    'quase_comuns_fields' => [
        'position_category',
        'workplace',
        'employment_bond',
        'bank',
        'salary_type',
        'salary_amount',
        'salary_words',
        'net_salary_amount',
        'net_salary_words',
    ],

    /*
    | Mapeamento: tipo de declaração -> campos do formulário (apenas os específicos do documento).
    | Os campos comuns (full_name, gender, issue_date, declaration_number, signer) são derivados
    | automaticamente pelo backend e não fazem parte do formulário.
    */
    'types' => [
        'informacao_salarial' => [
            'salutation', 'position', 'employment_bond', 'salary_type', 'salary_amount', 'salary_words',
            'net_salary_amount', 'net_salary_words',
        ],
        'actualizacao_categoria' => [
            'salutation', 'position', 'employment_bond', 'salary_type', 'salary_amount', 'salary_words',
        ],
        'actualizacao_conta_bancaria' => [
            'position_category', 'workplace', 'employment_bond', 'bank', 'salary_type',
            'salary_amount', 'salary_words',
        ],
        'adiantamento_salario' => [
            'position_category', 'workplace', 'service_time', 'admission_label', 'salary_type',
            'salary_amount', 'salary_words', 'account_number', 'bank',
            'id_card_number', 'employer_entity', 'admission_date',
            'consignment_account', 'paying_entity',
        ],
        'aquisicao_residencia' => [
            'residence', 'position_category', 'salary_type', 'salary_amount', 'salary_words',
        ],
        'concurso_publico' => [
            'position', 'employment_bond', 'issuing_department', 'signer_role',
        ],
        'consignacao_salarios' => [
            'account_number', 'id_card_number', 'phone', 'email', 'address', 'domicile_branch',
            'position_category', 'employment_bond', 'salary_amount', 'credit_purpose',
        ],
        'correccao_nome_sigfe' => [
            'correction_type', 'position_category', 'workplace', 'employment_bond',
        ],
        'bpc_salario' => [
            'position_category', 'workplace', 'admission_label', 'salary_type',
            'salary_amount', 'salary_words', 'account_number', 'credit_purpose',
        ],
        'credito_express' => [
            'id_card_number', 'employer_entity', 'position_category', 'admission_date',
            'salary_amount', 'salary_words', 'paying_entity', 'payment_day', 'consignment_account',
        ],
        'credito_pessoal' => [
            'id_card_number', 'employer_entity', 'workplace', 'position_category',
            'admission_date', 'salary_amount', 'salary_words',
            'paying_entity', 'payment_day', 'consignment_account',
        ],
        'junta_medica' => [
            'position_category', 'workplace', 'employment_bond',
        ],
        'mudanca_domicilio_bancario' => [
            'position_category', 'workplace', 'employment_bond',
        ],
        'cartao_debito' => [
            'position', 'employment_bond', 'salary_type', 'salary_amount', 'salary_words', 'bank',
        ],
        'obtencao_visto' => [
            'embassy', 'embassy_city', 'position_category', 'employment_bond', 'salary_type',
            'salary_amount', 'salary_words',
        ],
        'transferencia_domiciliacao_salario' => [
            'agent_number', 'service_time', 'position_category', 'salary_amount', 'salary_words',
            'account_number', 'bank', 'credit_purpose',
        ],
        'tutela_menor' => [
            'position_category', 'workplace', 'employment_bond',
        ],
    ],

    /*
    | Metadados de cada campo (rótulo, tipo de input, opções, grupo).
    */
    'fields' => [
        // Comuns
        'full_name' => [
            'label' => 'Nome completo',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'Nome completo em maiúsculas',
            'required' => true,
        ],
        'gender' => [
            'label' => 'Género',
            'type' => 'select',
            'group' => 'comum',
            'options' => ['masculino' => 'Masculino', 'feminino' => 'Feminino'],
            'required' => true,
        ],
        'issue_date' => [
            'label' => 'Data de emissão',
            'type' => 'date',
            'group' => 'comum',
            'required' => true,
        ],
        'declaration_number' => [
            'label' => 'Número da declaração',
            'type' => 'text',
            'group' => 'comum',
            'derived' => true,
            'description' => 'Gerado automaticamente pelo sistema (ex.: 0001/GAB-RH/2026).',
        ],
        'signer_name' => [
            'label' => 'Nome do signatário',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'Director do Gabinete de Recursos Humanos',
        ],
        'signer_role' => [
            'label' => 'Cargo do signatário',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'O DIRECTOR',
        ],

        // Quase-comuns
        'position_category' => [
            'label' => 'Categoria/Função',
            'type' => 'text',
            'group' => 'quase_comum',
            'placeholder' => 'Técnico Superior de 1ª Classe',
            'derived' => true,
            'description' => 'Preenchido automaticamente com a categoria de carreira do funcionário.',
        ],
        'workplace' => [
            'label' => 'Local de serviço',
            'type' => 'text',
            'group' => 'quase_comum',
            'placeholder' => 'Gabinete Jurídico e de Intercâmbio',
            'derived' => true,
            'description' => 'Preenchido automaticamente com o departamento do funcionário.',
        ],
        'employment_bond' => [
            'label' => 'Vínculo',
            'type' => 'select',
            'group' => 'quase_comum',
            'options' => [
                'Contrato de Trabalho por Tempo Indeterminado' => 'Contrato de Trabalho por Tempo Indeterminado',
                'Comissão de Serviço' => 'Comissão de Serviço',
                'Contrato de Trabalho por Tempo Determinado' => 'Contrato de Trabalho por Tempo Determinado',
                'Contrato de Prestação de Serviços' => 'Contrato de Prestação de Serviços',
                'Estagiário' => 'Estagiário',
            ],
            'derived' => true,
            'description' => 'Preenchido automaticamente com o tipo de contrato do funcionário.',
        ],
        'bank' => [
            'label' => 'Banco',
            'type' => 'select',
            'group' => 'quase_comum',
            'options' => [
                'BFA' => 'BFA',
                'BPC' => 'BPC',
                'BAI' => 'BAI',
                'BIC' => 'BIC',
                'Banco Sol' => 'Banco Sol',
                'BCA' => 'BCA',
                'Banco Nacional de Angola' => 'Banco Nacional de Angola',
            ],
            'derived' => true,
            'description' => 'Preenchido automaticamente com o banco do funcionário.',
        ],
        'salary_type' => [
            'label' => 'Tipo de salário',
            'type' => 'select',
            'group' => 'quase_comum',
            'options' => [
                'base' => 'Base',
                'liquido' => 'Líquido',
                'base_e_liquido' => 'Base e líquido',
            ],
            'derived' => true,
            'description' => 'Preenchido automaticamente a partir do registo do funcionário.',
        ],
        'salary_amount' => [
            'label' => 'Salário (valor numérico)',
            'type' => 'number',
            'group' => 'quase_comum',
            'step' => '0.01',
            'min' => '0',
            'derived' => true,
            'description' => 'Preenchido automaticamente com o salário base do funcionário.',
        ],
        'salary_words' => [
            'label' => 'Salário (por extenso)',
            'type' => 'text',
            'group' => 'quase_comum',
            'derived' => true,
        ],
        'net_salary_amount' => [
            'label' => 'Salário líquido (valor numérico)',
            'type' => 'number',
            'group' => 'quase_comum',
            'step' => '0.01',
            'min' => '0',
            'derived' => true,
            'description' => 'Preenchido automaticamente com o último vencimento líquido do funcionário.',
        ],
        'net_salary_words' => [
            'label' => 'Salário líquido (por extenso)',
            'type' => 'text',
            'group' => 'quase_comum',
            'derived' => true,
        ],

        // Específicos
        'salutation' => [
            'label' => 'Tratamento',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Sua Excelência, Eng.',
            'derived' => true,
            'description' => 'Preenchido automaticamente com base no género do funcionário.',
        ],
        'position' => [
            'label' => 'Cargo',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Chefe do Departamento de Gestão de Carreiras',
            'derived' => true,
            'description' => 'Preenchido automaticamente com o cargo do funcionário.',
        ],
        'service_time' => [
            'label' => 'Tempo de serviço',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => '12 anos',
            'derived' => true,
            'description' => 'Preenchido automaticamente a partir da data de admissão do funcionário.',
        ],
        'admission_label' => [
            'label' => 'Data de admissão (mês e ano)',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'desde Março de 2022',
            'derived' => true,
            'description' => 'Preenchido automaticamente com a data de admissão do funcionário.',
        ],
        'admission_date' => [
            'label' => 'Data de admissão (completa)',
            'type' => 'date',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Preenchido automaticamente com a data de admissão do funcionário.',
        ],
        'account_number' => [
            'label' => 'Número de conta',
            'type' => 'text',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Preenchido automaticamente com o número de conta/IBAN do funcionário.',
        ],
        'consignment_account' => [
            'label' => 'Conta de consignação',
            'type' => 'text',
            'group' => 'especifico',
        ],
        'id_card_number' => [
            'label' => 'Número do Bilhete de Identidade',
            'type' => 'text',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Preenchido automaticamente com o número do documento do funcionário.',
        ],
        'agent_number' => [
            'label' => 'Número de agente',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'N.º de agente 90963989',
        ],
        'phone' => [
            'label' => 'Telefone',
            'type' => 'text',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Preenchido automaticamente com o telefone do funcionário.',
        ],
        'email' => [
            'label' => 'E-mail',
            'type' => 'email',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Preenchido automaticamente com o e-mail do funcionário.',
        ],
        'address' => [
            'label' => 'Morada',
            'type' => 'textarea',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Preenchido automaticamente com a morada do funcionário.',
        ],
        'domicile_branch' => [
            'label' => 'Balcão de domiciliação',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Balcão da Cidade Alta',
        ],
        'credit_purpose' => [
            'label' => 'Finalidade do crédito',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Despesas pessoais',
        ],
        'employer_entity' => [
            'label' => 'Entidade empregadora',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Governo da Província do Huambo',
            'derived' => true,
            'description' => 'Preenchido automaticamente com a entidade empregadora padrão.',
        ],
        'paying_entity' => [
            'label' => 'Entidade pagadora',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Governo da Província do Huambo',
        ],
        'payment_day' => [
            'label' => 'Dia de pagamento',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'até ao dia 30 de cada mês',
        ],
        'embassy' => [
            'label' => 'Embaixada',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Embaixada de Portugal',
        ],
        'embassy_city' => [
            'label' => 'Cidade da embaixada',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Luanda',
        ],
        'residence' => [
            'label' => 'Local de residência',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Centralidade do Halavala, Município do Bailundo',
        ],
        'correction_type' => [
            'label' => 'Tipo de correcção',
            'type' => 'select',
            'group' => 'especifico',
            'options' => [
                'correccao' => 'Correcção',
                'acrescimo' => 'Acréscimo',
            ],
        ],
        'issuing_department' => [
            'label' => 'Departamento emissor',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Departamento de Gestão de Carreiras e Formação Técnica',
            'derived' => true,
            'description' => 'Preenchido automaticamente com o departamento emissor padrão.',
        ],
    ],
];
