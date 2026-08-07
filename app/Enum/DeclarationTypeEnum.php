<?php

namespace App\Enum;

enum DeclarationTypeEnum: string
{
    case Service = 'servico';
    case LaborLink = 'vinculo_laboral';
    case ServiceTime = 'tempo_servico';
    case Remuneration = 'vencimento';
    case Effectiveness = 'efetividade';
    case NoDisciplinary = 'ausencia_disciplinar';
    case Attendance = 'frequencia';
    case Retirement = 'aposentacao';
    case Compatibility = 'compatibilidade';
    case FunctionalStatus = 'situacao_funcional';

    private const LABELS = [
        'servico' => 'Declaração de Serviço',
        'vinculo_laboral' => 'Declaração de Vínculo Laboral',
        'tempo_servico' => 'Declaração de Tempo de Serviço',
        'vencimento' => 'Declaração de Vencimento ou Remuneração',
        'efetividade' => 'Declaração de Efetividade',
        'ausencia_disciplinar' => 'Declaração de Ausência de Processo Disciplinar',
        'frequencia' => 'Declaração de Frequência/Presença',
        'aposentacao' => 'Declaração para Efeitos de Aposentação',
        'compatibilidade' => 'Declaração de Compatibilidade ou Acumulação de Funções',
        'situacao_funcional' => 'Declaração de Situação Funcional',
    ];

    private const DESCRIPTIONS = [
        'servico' => 'Comprova que o funcionário exerce funções na instituição pública.',
        'vinculo_laboral' => 'Comprova a existência da relação jurídica de emprego público.',
        'tempo_servico' => 'Comprova o período em que o funcionário esteve ao serviço do Estado.',
        'vencimento' => 'Comprova os rendimentos recebidos pelo funcionário.',
        'efetividade' => 'Confirma que o funcionário está efetivamente em exercício de funções.',
        'ausencia_disciplinar' => 'Declara que o funcionário não possui processo disciplinar pendente ou registado.',
        'frequencia' => 'Comprova que o funcionário comparece regularmente ao serviço.',
        'aposentacao' => 'Documento utilizado para instrução de processos junto das entidades responsáveis pela aposentação.',
        'compatibilidade' => 'Relacionada com a autorização para exercer outras atividades públicas ou privadas.',
        'situacao_funcional' => 'Documento geral que informa a situação atual do funcionário.',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value] ?? $this->value;
    }

    public function description(): string
    {
        return self::DESCRIPTIONS[$this->value] ?? '';
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return array_map(fn($case) => $case->label(), self::cases());
    }
}
