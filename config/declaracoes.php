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
*/

return [

    /*
    | Campos comuns — presentes em TODOS os tipos de declaração.
    */
    'common_fields' => [
        'nome_completo',
        'sexo',
        'data_emissao',
        'numero_declaracao',
        'assinante_nome',
        'assinante_cargo',
    ],

    /*
    | Campos quase-comuns — usados em muitos tipos, mas não em todos.
    */
    'quase_comuns_fields' => [
        'categoria_funcao',
        'local_servico',
        'vinculo',
        'banco',
        'tipo_salario',
        'salario_numero',
        'salario_extenso',
        'salario_numero_liquido',
        'salario_extenso_liquido',
    ],

    /*
    | Mapeamento: tipo de declaração -> campos específicos (para além dos comuns).
    */
    'types' => [
        'informacao_salarial' => [
            'tratamento', 'cargo', 'vinculo', 'tipo_salario', 'salario_numero', 'salario_extenso',
            'salario_numero_liquido', 'salario_extenso_liquido',
        ],
        'actualizacao_categoria' => [
            'tratamento', 'cargo', 'vinculo', 'tipo_salario', 'salario_numero', 'salario_extenso',
        ],
        'actualizacao_conta_bancaria' => [
            'categoria_funcao', 'local_servico', 'vinculo', 'banco', 'tipo_salario',
            'salario_numero', 'salario_extenso',
        ],
        'adiantamento_salario' => [
            'categoria_funcao', 'local_servico', 'tempo_servico', 'data_admissao', 'tipo_salario',
            'salario_numero', 'salario_extenso', 'numero_conta', 'banco',
            'numero_bi', 'entidade_empregadora', 'data_admissao_completa',
            'conta_consignacao', 'entidade_pagadora',
        ],
        'aquisicao_residencia' => [
            'local_residencia', 'categoria_funcao', 'tipo_salario', 'salario_numero', 'salario_extenso',
        ],
        'concurso_publico' => [
            'cargo', 'vinculo', 'departamento_emissor', 'assinante_cargo',
        ],
        'consignacao_salarios' => [
            'numero_conta', 'numero_bi', 'telefone', 'email', 'morada', 'balcao_domicilio',
            'categoria_funcao', 'vinculo', 'salario_numero', 'finalidade',
        ],
        'correccao_nome_sigfe' => [
            'tipo_correccao', 'categoria_funcao', 'local_servico', 'vinculo',
        ],
        'bpc_salario' => [
            'categoria_funcao', 'local_servico', 'data_admissao', 'tipo_salario',
            'salario_numero', 'salario_extenso', 'numero_conta', 'finalidade',
        ],
        'credito_express' => [
            'numero_bi', 'entidade_empregadora', 'categoria_funcao', 'data_admissao_completa',
            'salario_numero', 'salario_extenso', 'entidade_pagadora', 'dia_pagamento', 'conta_consignacao',
        ],
        'credito_pessoal' => [
            'numero_bi', 'entidade_empregadora', 'local_servico', 'categoria_funcao',
            'data_admissao_completa', 'salario_numero', 'salario_extenso',
            'entidade_pagadora', 'dia_pagamento', 'conta_consignacao',
        ],
        'junta_medica' => [
            'categoria_funcao', 'local_servico', 'vinculo',
        ],
        'mudanca_domicilio_bancario' => [
            'categoria_funcao', 'local_servico', 'vinculo',
        ],
        'cartao_debito' => [
            'cargo', 'vinculo', 'tipo_salario', 'salario_numero', 'salario_extenso', 'banco',
        ],
        'obtencao_visto' => [
            'embaixada', 'cidade_embaixada', 'categoria_funcao', 'vinculo', 'tipo_salario',
            'salario_numero', 'salario_extenso',
        ],
        'transferencia_domiciliacao_salario' => [
            'numero_agente', 'tempo_servico', 'categoria_funcao', 'salario_numero', 'salario_extenso',
            'numero_conta', 'banco', 'finalidade',
        ],
        'tutela_menor' => [
            'categoria_funcao', 'local_servico', 'vinculo',
        ],
    ],

    /*
    | Metadados de cada campo (rótulo, tipo de input, opções, grupo).
    */
    'fields' => [
        // Comuns
        'nome_completo' => [
            'label' => 'Nome completo',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'Nome completo em maiúsculas',
            'required' => true,
        ],
        'sexo' => [
            'label' => 'Sexo',
            'type' => 'select',
            'group' => 'comum',
            'options' => ['masculino' => 'Masculino', 'feminino' => 'Feminino'],
            'required' => true,
        ],
        'data_emissao' => [
            'label' => 'Data de emissão',
            'type' => 'date',
            'group' => 'comum',
            'required' => true,
        ],
        'numero_declaracao' => [
            'label' => 'Número da declaração',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'N.º 45/026',
        ],
        'assinante_nome' => [
            'label' => 'Nome do assinante',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'Director do Gabinete de Recursos Humanos',
        ],
        'assinante_cargo' => [
            'label' => 'Cargo do assinante',
            'type' => 'text',
            'group' => 'comum',
            'placeholder' => 'O DIRECTOR',
        ],

        // Quase-comuns
        'categoria_funcao' => [
            'label' => 'Categoria/Função',
            'type' => 'text',
            'group' => 'quase_comum',
            'placeholder' => 'Técnico Superior de 1ª Classe',
        ],
        'local_servico' => [
            'label' => 'Local de serviço',
            'type' => 'text',
            'group' => 'quase_comum',
            'placeholder' => 'Gabinete Jurídico e de Intercâmbio',
        ],
        'vinculo' => [
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
        ],
        'banco' => [
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
        ],
        'tipo_salario' => [
            'label' => 'Tipo de salário',
            'type' => 'select',
            'group' => 'quase_comum',
            'options' => [
                'base' => 'Base',
                'liquido' => 'Líquido',
                'base_e_liquido' => 'Base e líquido',
            ],
        ],
        'salario_numero' => [
            'label' => 'Salário (valor numérico)',
            'type' => 'number',
            'group' => 'quase_comum',
            'step' => '0.01',
            'min' => '0',
        ],
        'salario_extenso' => [
            'label' => 'Salário (por extenso)',
            'type' => 'text',
            'group' => 'quase_comum',
            'derived' => true,
        ],
        'salario_numero_liquido' => [
            'label' => 'Salário líquido (valor numérico)',
            'type' => 'number',
            'group' => 'quase_comum',
            'step' => '0.01',
            'min' => '0',
        ],
        'salario_extenso_liquido' => [
            'label' => 'Salário líquido (por extenso)',
            'type' => 'text',
            'group' => 'quase_comum',
            'derived' => true,
        ],

        // Específicos
        'tratamento' => [
            'label' => 'Tratamento',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Sua Excelência, Eng.º',
        ],
        'cargo' => [
            'label' => 'Cargo',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Chefe de Departamento de Gestão de Carreiras',
        ],
        'tempo_servico' => [
            'label' => 'Tempo de serviço',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'há 12 anos',
        ],
        'data_admissao' => [
            'label' => 'Data de admissão (mês e ano)',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'desde Março de 2022',
        ],
        'data_admissao_completa' => [
            'label' => 'Data de admissão (completa)',
            'type' => 'date',
            'group' => 'especifico',
        ],
        'numero_conta' => [
            'label' => 'Número de conta',
            'type' => 'text',
            'group' => 'especifico',
        ],
        'conta_consignacao' => [
            'label' => 'Conta de consignação',
            'type' => 'text',
            'group' => 'especifico',
        ],
        'numero_bi' => [
            'label' => 'Número do Bilhete de Identidade',
            'type' => 'text',
            'group' => 'especifico',
        ],
        'numero_agente' => [
            'label' => 'Número de agente',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Agente n.º 90963989',
        ],
        'telefone' => [
            'label' => 'Telefone',
            'type' => 'text',
            'group' => 'especifico',
        ],
        'email' => [
            'label' => 'Email',
            'type' => 'email',
            'group' => 'especifico',
        ],
        'morada' => [
            'label' => 'Morada',
            'type' => 'textarea',
            'group' => 'especifico',
        ],
        'balcao_domicilio' => [
            'label' => 'Balcão de domicílio',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Agência Cidade Alta',
        ],
        'finalidade' => [
            'label' => 'Finalidade',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Despesas Pessoais',
        ],
        'entidade_empregadora' => [
            'label' => 'Entidade empregadora',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Governo da Província do Huambo',
        ],
        'entidade_pagadora' => [
            'label' => 'Entidade pagadora',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Governo da Província do Huambo',
        ],
        'dia_pagamento' => [
            'label' => 'Dia de pagamento',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'até ao dia 30 de cada mês',
        ],
        'embaixada' => [
            'label' => 'Embaixada',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Embaixada de Portugal',
        ],
        'cidade_embaixada' => [
            'label' => 'Cidade da embaixada',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Luanda',
        ],
        'local_residencia' => [
            'label' => 'Local de residência',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Centralidade Halavala, do Município do Bailundo',
        ],
        'tipo_correccao' => [
            'label' => 'Tipo de correcção',
            'type' => 'select',
            'group' => 'especifico',
            'options' => [
                'correccao' => 'Correcção',
                'acrescimo' => 'Acréscimo',
            ],
        ],
        'departamento_emissor' => [
            'label' => 'Departamento emissor',
            'type' => 'text',
            'group' => 'especifico',
            'placeholder' => 'Departamento de Gestão de Carreiras e Capacitação Técnica',
        ],
    ],
];
