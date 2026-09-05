<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Norma de Excepção ao Registo de Ponto (RH)
    |--------------------------------------------------------------------------
    | Gabinetes que NÃO assinam o livro de ponto no RH (têm livro próprio).
    | Funcionários destes departamentos não devem aparecer no registo de
    | ponto do RH (selecção, check-in/out, faltas e importação biométrica)
    | nem ser marcados como faltas pelo sistema automático.
    |
    | A regra é centralizada em App\Support\PontoExceptions e pode ser
    | parametrizada por CÓDIGO de departamento (recomendado) e/ou NOME.
    */
    'ponto' => [
        'exempt_department_codes' => [
            'GAB-GOV', // Gabinete do Governador
            'GAB-COM', // Gabinete de Comunicação Social
            'GEPE', // Gabinete de Estudos, Planeamento e Estatística
        ],

        'exempt_department_names' => [
            'GEPE',
            'Gabinete do Governador',
            'Comunicação Social',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dispensas / Solicitações de Assiduidade
    |--------------------------------------------------------------------------
    | Tipos de solicitação de dispensa e regras associadas, em conformidade
    | com a Lei n.º 26/22 de 22 de Agosto (Lei de Bases da Função Pública).
    |
    | - amamentacao (art. 93.º, n.º 2): direito a dois períodos distintos de
    |   duração máxima de uma hora cada (até 2h/dia) no horário da função
    |   pública (08:00–15:00), enquanto durar e até o filho perfazer 18 meses.
    | - pre_natal (art. 93.º, n.º 1): consultas pré-natais pelo tempo e número
    |   de vezes clinicamente determinados (sem limite fixo).
    | - relatorio_medico (art. 90.º): até 30 dias com relatório médico,
    |   prorrogável uma única vez pelo mesmo período; período superior é
    |   submetido à Junta Médica (max_days = 30).
    | - acompanhamento_deficiencia (art. 96.º): pessoa com necessidades
    |   especiais, pelo tempo e número de vezes clinicamente recomendados.
    */
    'dispensa' => [
        'work_start' => '08:00',
        'work_end' => '15:00',
        'breastfeeding_reduction_hours' => 2,
        'breastfeeding_max_months' => 18,
        'max_days_messages' => [
            30 => 'O período máximo para pedidos por relatório médico é de 30 dias (art. 90.º da Lei n.º 26/22); períodos superiores são submetidos à Junta Médica.',
        ],

        'statuses' => [
            'pending' => 'Pendente',
            'under_review' => 'Em análise',
            'approved' => 'Aprovada',
            'rejected' => 'Rejeitada',
            'cancelled' => 'Cancelada',
        ],

        'types' => [
            [
                'code' => 'dispensa',
                'name' => 'Pedido de dispensa',
                'description' => 'Dispensa do serviço por motivo devidamente justificado.',
                'required_documents' => [],
                'max_days' => null,
            ],
            [
                'code' => 'amamentacao',
                'name' => 'Dispensa para amamentação (art. 93.º da Lei n.º 26/22)',
                'description' => 'Dois períodos diários de até uma hora cada, enquanto durar e até o filho perfazer 18 meses; horário escolhido pela funcionária, sem diminuição do salário.',
                'required_documents' => ['cedula_crianca', 'alta_hospitalar', 'bi_mae', 'requerimento'],
                'max_days' => null,
            ],
            [
                'code' => 'pre_natal',
                'name' => 'Dispensa para consultas pré-natais (art. 93.º, n.º 1 da Lei n.º 26/22)',
                'description' => 'Dispensa do serviço pelo tempo e número de vezes clinicamente determinados.',
                'required_documents' => ['relatorio_medico', 'requerimento'],
                'max_days' => null,
            ],
            [
                'code' => 'relatorio_medico',
                'name' => 'Dispensa por relatório médico (art. 90.º da Lei n.º 26/22)',
                'description' => 'Até 30 dias com relatório médico, prorrogável uma única vez pelo mesmo período; período superior é submetido à Junta Médica.',
                'required_documents' => ['relatorio_medico'],
                'max_days' => 30,
            ],
            [
                'code' => 'acompanhamento_deficiencia',
                'name' => 'Dispensa para acompanhamento de pessoa com necessidades especiais (art. 96.º da Lei n.º 26/22)',
                'description' => 'Dispensa do trabalho pelo tempo e número de vezes clinicamente recomendados para cuidar de pessoa com necessidades especiais sob a sua responsabilidade.',
                'required_documents' => ['documento_comprovativo'],
                'max_days' => null,
            ],
            [
                'code' => 'outro',
                'name' => 'Outra dispensa',
                'description' => 'Outro tipo de dispensa não previsto nos demais tipos.',
                'required_documents' => [],
                'max_days' => null,
            ],
        ],

        'document_codes' => [
            'cedula_crianca' => 'Cédula da criança',
            'alta_hospitalar' => 'Título de alta do hospital/maternidade',
            'bi_mae' => 'Bilhete de identidade da mãe',
            'requerimento' => 'Requerimento dirigido ao Director de RH',
            'relatorio_medico' => 'Relatório médico',
            'documento_comprovativo' => 'Documento comprovativo',
            'outro' => 'Outro documento',
        ],
    ],
];
