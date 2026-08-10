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
        // Common
        'full_name' => [
            'label' => 'Full name',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'Full name in capital letters',
            'required' => true,
        ],
        'gender' => [
            'label' => 'Gender',
            'type' => 'select',
            'group' => 'comum',
            'options' => ['masculino' => 'Male', 'feminino' => 'Female'],
            'required' => true,
        ],
        'issue_date' => [
            'label' => 'Issue date',
            'type' => 'date',
            'group' => 'comum',
            'required' => true,
        ],
        'declaration_number' => [
            'label' => 'Declaration number',
            'type' => 'text',
            'group' => 'comum',
            'derived' => true,
            'description' => 'Generated automatically by the system (e.g.: 0001/GAB-RH/2026).',
        ],
        'signer_name' => [
            'label' => 'Signer name',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'Director of the Human Resources Office',
        ],
        'signer_role' => [
            'label' => 'Signer role',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'THE DIRECTOR',
        ],

        // Almost-common
        'position_category' => [
            'label' => 'Position/Category',
            'type' => 'text',
            'group' => 'quase_comum',
            'placeholder' => 'Senior Technician 1st Class',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s career category.',
        ],
        'workplace' => [
            'label' => 'Workplace',
            'type' => 'text',
            'group' => 'quase_comum',
            'placeholder' => 'Legal and Exchange Office',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s department.',
        ],
        'employment_bond' => [
            'label' => 'Employment bond',
            'type' => 'select',
            'group' => 'quase_comum',
            'options' => [
                'Contrato de Trabalho por Tempo Indeterminado' => 'Open-ended employment contract',
                'Comissão de Serviço' => 'Service commission',
                'Contrato de Trabalho por Tempo Determinado' => 'Fixed-term employment contract',
                'Contrato de Prestação de Serviços' => 'Service provision contract',
                'Estagiário' => 'Intern',
            ],
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s contract type.',
        ],
        'bank' => [
            'label' => 'Bank',
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
            'description' => 'Filled automatically with the employee\'s bank.',
        ],
        'salary_type' => [
            'label' => 'Salary type',
            'type' => 'select',
            'group' => 'quase_comum',
            'options' => [
                'base' => 'Base',
                'liquido' => 'Net',
                'base_e_liquido' => 'Base and net',
            ],
            'derived' => true,
            'description' => 'Filled automatically from the employee record.',
        ],
        'salary_amount' => [
            'label' => 'Salary (numeric value)',
            'type' => 'number',
            'group' => 'quase_comum',
            'step' => '0.01',
            'min' => '0',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s base salary.',
        ],
        'salary_words' => [
            'label' => 'Salary (in words)',
            'type' => 'text',
            'group' => 'quase_comum',
            'derived' => true,
        ],
        'net_salary_amount' => [
            'label' => 'Net salary (numeric value)',
            'type' => 'number',
            'group' => 'quase_comum',
            'step' => '0.01',
            'min' => '0',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s latest net pay.',
        ],
        'net_salary_words' => [
            'label' => 'Net salary (in words)',
            'type' => 'text',
            'group' => 'quase_comum',
            'derived' => true,
        ],

        // Specific
        'salutation' => [
            'label' => 'Salutation',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Your Excellency, Eng.',
            'derived' => true,
            'description' => 'Filled automatically based on the employee\'s gender.',
        ],
        'position' => [
            'label' => 'Position',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Head of Career Management Department',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s position.',
        ],
        'service_time' => [
            'label' => 'Service time',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => '12 years',
            'derived' => true,
            'description' => 'Filled automatically from the employee\'s hire date.',
        ],
        'admission_label' => [
            'label' => 'Hire date (month and year)',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'since March 2022',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s hire date.',
        ],
        'admission_date' => [
            'label' => 'Hire date (full)',
            'type' => 'date',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s hire date.',
        ],
        'account_number' => [
            'label' => 'Account number',
            'type' => 'text',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s IBAN/account number.',
        ],
        'consignment_account' => [
            'label' => 'Consignment account',
            'type' => 'text',
            'group' => 'especifico',
        ],
        'id_card_number' => [
            'label' => 'Identity Card number',
            'type' => 'text',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s document number.',
        ],
        'agent_number' => [
            'label' => 'Agent number',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Agent no. 90963989',
        ],
        'phone' => [
            'label' => 'Phone',
            'type' => 'text',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s phone number.',
        ],
        'email' => [
            'label' => 'Email',
            'type' => 'email',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s email.',
        ],
        'address' => [
            'label' => 'Address',
            'type' => 'textarea',
            'group' => 'especifico',
            'derived' => true,
            'description' => 'Filled automatically with the employee\'s address.',
        ],
        'domicile_branch' => [
            'label' => 'Domicile branch',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Cidade Alta branch',
        ],
        'credit_purpose' => [
            'label' => 'Credit purpose',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Personal expenses',
        ],
        'employer_entity' => [
            'label' => 'Employer entity',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Governo da Província do Huambo',
            'derived' => true,
            'description' => 'Filled automatically with the default employer entity.',
        ],
        'paying_entity' => [
            'label' => 'Paying entity',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Governo da Província do Huambo',
        ],
        'payment_day' => [
            'label' => 'Payment day',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'until the 30th of each month',
        ],
        'embassy' => [
            'label' => 'Embassy',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Embassy of Portugal',
        ],
        'embassy_city' => [
            'label' => 'Embassy city',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Luanda',
        ],
        'residence' => [
            'label' => 'Place of residence',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Halavala Centrality, Bailundo Municipality',
        ],
        'correction_type' => [
            'label' => 'Correction type',
            'type' => 'select',
            'group' => 'especifico',
            'options' => [
                'correccao' => 'Correction',
                'acrescimo' => 'Increase',
            ],
        ],
        'issuing_department' => [
            'label' => 'Issuing department',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Career Management and Technical Training Department',
            'derived' => true,
            'description' => 'Filled automatically with the default issuing department.',
        ],
    ],
];
