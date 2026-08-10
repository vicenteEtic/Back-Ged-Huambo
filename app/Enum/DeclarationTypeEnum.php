<?php

namespace App\Enum;

enum DeclarationTypeEnum: string
{
    case TutelaMenor = 'tutela_menor';
    case CorreccaoNomeSigfe = 'correccao_nome_sigfe';
    case JuntaMedica = 'junta_medica';
    case ActualizacaoCategoria = 'actualizacao_categoria';
    case MudancaDomicilioBancario = 'mudanca_domicilio_bancario';
    case InformacaoSalarial = 'informacao_salarial';
    case ConcursoPublico = 'concurso_publico';
    case ObtencaoVisto = 'obtencao_visto';
    case AquisicaoResidencia = 'aquisicao_residencia';
    case AdiantamentoSalario = 'adiantamento_salario';
    case BpcSalario = 'bpc_salario';
    case ConsignacaoSalarios = 'consignacao_salarios';
    case CreditoExpress = 'credito_express';
    case CreditoPessoal = 'credito_pessoal';
    case ActualizacaoContaBancaria = 'actualizacao_conta_bancaria';
    case CartaoDebito = 'cartao_debito';
    case TransferenciaDomiciliacaoSalario = 'transferencia_domiciliacao_salario';

    private const LABELS = [
        'tutela_menor' => 'Declaração para Tutela de Menor',
        'correccao_nome_sigfe' => 'Declaração para Correcção de Nome (SIGFE)',
        'junta_medica' => 'Declaração para Junta Médica',
        'actualizacao_categoria' => 'Declaração para Actualização de Categoria',
        'mudanca_domicilio_bancario' => 'Declaração para Mudança de Domicílio Bancário',
        'informacao_salarial' => 'Declaração de Informação Salarial',
        'concurso_publico' => 'Declaração para Concurso Público (Ensino Superior)',
        'obtencao_visto' => 'Declaração para Obtenção de Visto',
        'aquisicao_residencia' => 'Declaração para Aquisição de Residência',
        'adiantamento_salario' => 'Declaração para Adiantamento de Salário',
        'bpc_salario' => 'Declaração para Crédito de Salário (BPC)',
        'consignacao_salarios' => 'Declaração para Consignação de Salários',
        'credito_express' => 'Declaração para Crédito Express',
        'credito_pessoal' => 'Declaração para Crédito Pessoal',
        'actualizacao_conta_bancaria' => 'Declaração para Actualização de Conta Bancária',
        'cartao_debito' => 'Declaração para Obtenção de Cartão de Débito',
        'transferencia_domiciliacao_salario' => 'Declaração para Transferência/Domiciliação de Salário',
    ];

    private const DESCRIPTIONS = [
        'tutela_menor' => 'Comprova a situação laboral e remuneratória do trabalhador para efeitos de tutela de menor.',
        'correccao_nome_sigfe' => 'Comprova os dados do trabalhador para correcção de nome no sistema SIGFE.',
        'junta_medica' => 'Instrução de processo de junta médica com informação da situação funcional do trabalhador.',
        'actualizacao_categoria' => 'Comprova a categoria e o tempo de serviço do trabalhador para actualização de categoria.',
        'mudanca_domicilio_bancario' => 'Comprova a situação funcional do trabalhador para mudança de domicílio bancário do salário.',
        'informacao_salarial' => 'Informação salarial do trabalhador para os efeitos tidos e achados por convenientes.',
        'concurso_publico' => 'Comprova a situação funcional do trabalhador para candidatura a concurso público no ensino superior.',
        'obtencao_visto' => 'Comprova a situação laboral do trabalhador para efeitos de obtenção de visto.',
        'aquisicao_residencia' => 'Comprova a situação funcional e remuneratória do trabalhador para aquisição de residência.',
        'adiantamento_salario' => 'Declaração de vencimento para pedido de adiantamento de salário junto de instituição bancária.',
        'bpc_salario' => 'Comprova o vínculo laboral e a remuneração para obtenção de crédito de salário junto do BPC.',
        'consignacao_salarios' => 'Declaração de vencimento para consignação de salários junto de instituição bancária.',
        'credito_express' => 'Declaração de vencimento para obtenção de crédito express junto de instituição bancária.',
        'credito_pessoal' => 'Declaração de vencimento para obtenção de crédito pessoal junto de instituição bancária.',
        'actualizacao_conta_bancaria' => 'Comprova a situação funcional e remuneratória do trabalhador para actualização de conta bancária.',
        'cartao_debito' => 'Comprova a situação funcional do trabalhador para obtenção de cartão de débito.',
        'transferencia_domiciliacao_salario' => 'Comprova a situação funcional do trabalhador para transferência/domiciliação de salário a instituição bancária.',
    ];

    private const APPROVAL_REQUIRED = [
        'tutela_menor',
        'correccao_nome_sigfe',
        'junta_medica',
        'actualizacao_categoria',
        'informacao_salarial',
        'concurso_publico',
        'obtencao_visto',
        'aquisicao_residencia',
        'adiantamento_salario',
        'bpc_salario',
        'consignacao_salarios',
        'credito_express',
        'credito_pessoal',
        'transferencia_domiciliacao_salario',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value] ?? $this->value;
    }

    public function description(): string
    {
        return self::DESCRIPTIONS[$this->value] ?? '';
    }

    public function requiresApproval(): bool
    {
        return in_array($this->value, self::APPROVAL_REQUIRED);
    }

    /**
     * Campos específicos do tipo de declaração.
     */
    public function fields(): array
    {
        return config('declaracoes.types.'.$this->value, []);
    }

    /**
     * Todos os campos do formulário: apenas os específicos do tipo.
     * Os campos comuns são auto-preenchidos pelo backend.
     */
    public function formFields(): array
    {
        return $this->fields();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return array_map(fn ($case) => $case->label(), self::cases());
    }
}
