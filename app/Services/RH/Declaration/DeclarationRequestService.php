<?php

namespace App\Services\RH\Declaration;

use App\Models\RH\Declaration\DeclarationRequest;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Payroll\Payslip;
use App\Notifications\RH\DeclarationRequestNotification;
use App\Repositories\RH\Declaration\DeclarationRequestRepository;
use App\Services\AbstractService;
use App\Services\RH\Career\CareerService;
use App\Support\DeclarationText;
use App\Support\NumberToWordsPt;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DeclarationRequestService extends AbstractService
{
    /**
     * Campos de formulário que não aparecem directamente no documento
     * (são tratados/combinados pela lógica salarial).
     */
    private const NON_DOCUMENT_FIELDS = [
        'salary_amount',
        'salary_words',
        'net_salary_amount',
        'net_salary_words',
        'salary_type',
    ];

    public function __construct(
        DeclarationRequestRepository $repository,
        protected CareerService $careerService,
    ) {
        parent::__construct($repository);
    }

    public function submit(array $data): DeclarationRequest
    {
        return DB::transaction(function () use ($data) {
            $typeId = $data['declaration_type_id'];
            $employeeId = $data['employee_id'];

            $declarationType = $this->findType($typeId);
            $employee = $this->findEmployee($employeeId);

            $data = $this->applyDerivedValues($data, $employee);

            $data['status'] = $declarationType->requires_approval ? 'pending' : 'approved';
            $data['content'] = $this->generateContent($declarationType->code, $employee, $data);

            $declarationRequest = $this->store($data);

            if ($declarationRequest->status === 'pending') {
                $this->notifyApprovers($declarationRequest);
            }

            return $declarationRequest->fresh(['employee', 'declarationType', 'approvedBy', 'issuedBy']);
        });
    }

    public function preview(int $typeId, int $employeeId, ?array $context = null): array
    {
        $declarationType = $this->findType($typeId);
        $employee = $this->findEmployee($employeeId);

        $context = $this->applyDerivedValues($context ?? [], $employee);

        return [
            'declaration_type' => [
                'id' => $declarationType->id,
                'code' => $declarationType->code,
                'name' => $declarationType->name,
                'description' => $declarationType->description,
            ],
            'content' => $this->generateContent($declarationType->code, $employee, $context),
        ];
    }

    public function previewRequest(int $id): array
    {
        $request = DeclarationRequest::with('employee', 'declarationType')->findOrFail($id);

        return [
            'reference_number' => $request->reference_number,
            'declaration_type' => $request->declarationType?->name,
            'content' => $this->generateContent(
                $request->declarationType->code,
                $request->employee,
                $this->requestFields($request)
            ),
        ];
    }

    public function approve(int $id, int $userId, ?string $comment = null): DeclarationRequest
    {
        return DB::transaction(function () use ($id, $userId, $comment) {
            $request = DeclarationRequest::with('employee.user')->findOrFail($id);

            if ($request->status === 'issued') {
                throw new DomainException('Esta declaração já foi emitida e não pode ser alterada.');
            }

            if ($request->status === 'rejected') {
                throw new DomainException('Esta declaração foi rejeitada. Submeta um novo pedido.');
            }

            $request->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'notes' => $comment ?? $request->notes,
            ]);

            $this->notifyEmployee($request, 'approved', $comment);

            return $request->fresh(['employee', 'declarationType', 'approvedBy', 'issuedBy']);
        });
    }

    public function reject(int $id, int $userId, string $reason): DeclarationRequest
    {
        return DB::transaction(function () use ($id, $userId, $reason) {
            $request = DeclarationRequest::with('employee.user')->findOrFail($id);

            if ($request->status === 'issued') {
                throw new DomainException('Esta declaração já foi emitida e não pode ser rejeitada.');
            }

            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            $this->notifyEmployee($request, 'rejected', $reason);

            return $request->fresh(['employee', 'declarationType', 'approvedBy', 'issuedBy']);
        });
    }

    public function issue(int $id, int $userId): DeclarationRequest
    {
        return DB::transaction(function () use ($id, $userId) {
            $request = DeclarationRequest::with('employee', 'declarationType')->findOrFail($id);

            if ($request->status === 'rejected') {
                throw new DomainException('Não é possível emitir uma declaração rejeitada.');
            }

            if (empty($request->issued_number)) {
                $request->issued_number = $this->generateIssuedNumber($request);
            }

            $fields = $this->requestFields($request);

            if (empty($fields['issue_date'])) {
                $fields['issue_date'] = now()->toDateString();
            }

            $request->update([
                'content' => $this->generateContent(
                    $request->declarationType->code,
                    $request->employee,
                    $fields
                ),
                'status' => 'issued',
                'issued_by' => $userId,
                'issued_at' => now(),
            ]);

            return $request->fresh(['employee', 'declarationType', 'approvedBy', 'issuedBy']);
        });
    }

    public function pending(): \Illuminate\Support\Collection
    {
        return DeclarationRequest::with(['employee', 'declarationType'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
    }

    public function generateContent(string $code, Employee $employee, ?array $context = null): array
    {
        $employee->load(['position', 'department', 'careerCategory']);
        $career = $this->careerService->calculate($employee);
        $payslip = Payslip::where('employee_id', $employee->id)
            ->latest('generated_at')
            ->first();

        $context = $context ?? [];
        $declaration = $this->extractDeclarationFields($context);
        $context = $this->applyDerivedValues($context, $employee);

        $data = [
            'type' => $code,
            'generated_at' => now()->toDateTimeString(),
            'employee' => $this->employeeData($employee),
            'career' => $career,
            'remuneration' => $this->remunerationData($employee, $payslip),
            'declaration' => $declaration,
        ];

        return match ($code) {
            'tutela_menor' => $this->tutelaMenorDeclaration($data, $employee, $context),
            'correccao_nome_sigfe' => $this->correccaoNomeDeclaration($data, $employee, $context),
            'junta_medica' => $this->juntaMedicaDeclaration($data, $employee, $context),
            'actualizacao_categoria' => $this->actualizacaoCategoriaDeclaration($data, $employee, $context),
            'mudanca_domicilio_bancario' => $this->mudancaDomicilioBancarioDeclaration($data, $employee, $context),
            'informacao_salarial' => $this->informacaoSalarialDeclaration($data, $employee, $context),
            'concurso_publico' => $this->concursoPublicoDeclaration($data, $employee, $context),
            'obtencao_visto' => $this->obtencaoVistoDeclaration($data, $employee, $context),
            'aquisicao_residencia' => $this->aquisicaoResidenciaDeclaration($data, $employee, $context),
            'adiantamento_salario' => $this->adiantamentoSalarioDeclaration($data, $employee, $context),
            'bpc_salario' => $this->bpcSalarioDeclaration($data, $employee, $context),
            'consignacao_salarios' => $this->consignacaoSalariosDeclaration($data, $employee, $context),
            'credito_express' => $this->creditoExpressDeclaration($data, $employee, $context),
            'credito_pessoal' => $this->creditoPessoalDeclaration($data, $employee, $context),
            'actualizacao_conta_bancaria' => $this->actualizacaoContaBancariaDeclaration($data, $employee, $context),
            'cartao_debito' => $this->cartaoDebitoDeclaration($data, $employee, $context),
            'transferencia_domiciliacao_salario' => $this->transferenciaDomiciliacaoDeclaration($data, $employee, $context),
            default => $data,
        };
    }

    private function tutelaMenorDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Tutela de Menor',
            "Declara-se, para efeitos de tutela de menor, que {$this->subject($data, $employee)} exerce funções na instituição desde {$this->dateLabel($employee->hire_date)}.",
            $this->typeFields('tutela_menor', $data)
        );
    }

    private function correccaoNomeDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Correcção de Nome (SIGFE)',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data).' da instituição, para efeitos de correcção de nome junto do SIGFE.',
            array_merge($this->typeFields('correccao_nome_sigfe', $data), [
                'Documento de identificação' => ($employee->document_type ?? 'N/A').' '.($employee->document_number ?? ''),
                'NIF' => $employee->nif ?? 'N/A',
                'Data de nascimento' => $this->dateLabel($employee->date_of_birth),
            ])
        );
    }

    private function juntaMedicaDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Junta Médica',
            "Declara-se que {$this->subject($data, $employee)} se encontra ao serviço da instituição desde {$this->dateLabel($employee->hire_date)}, para instrução de processo de junta médica.",
            array_merge($this->typeFields('junta_medica', $data), [
                'Data de nascimento' => $this->dateLabel($employee->date_of_birth),
                'Tempo de serviço acumulado' => $data['career']['total_service']['formatted'] ?? 'N/A',
            ])
        );
    }

    private function actualizacaoCategoriaDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Actualização de Categoria',
            "Declara-se que {$this->subject($data, $employee)} desempenha funções na categoria de ".($data['employee']['category'] ?? 'N/A').', para efeitos de actualização de categoria.',
            array_merge($this->salaryFields('actualizacao_categoria', $data), $this->careerFields($data))
        );
    }

    private function mudancaDomicilioBancarioDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Mudança de Domicílio Bancário',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data).' da instituição, com vencimento processado pela folha de salários, para efeitos de mudança de domicílio bancário.',
            array_merge($this->typeFields('mudanca_domicilio_bancario', $data), $this->bankFields($employee))
        );
    }

    private function informacaoSalarialDeclaration(array $data, Employee $employee, ?array $context): array
    {
        $salary = $data['declaration']['salary_amount'] ?? $data['remuneration']['base_salary'];

        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração de Informação Salarial',
            "Declara-se que {$this->subject($data, $employee)}, ".$this->funcionario($data)." da instituição, aufere de vencimento base o valor de {$this->money($salary)}, para os efeitos tidos e achados por convenientes.",
            $this->salaryFields('informacao_salarial', $data)
        );
    }

    private function concursoPublicoDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Concurso Público (Ensino Superior)',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data).' da instituição, para efeitos de candidatura a concurso público no ensino superior.',
            array_merge($this->typeFields('concurso_publico', $data), $this->careerFields($data))
        );
    }

    private function obtencaoVistoDeclaration(array $data, Employee $employee, ?array $context): array
    {
        $embassy = $data['declaration']['embassy'] ?? null;

        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Obtenção de Visto',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data)." da instituição desde {$this->dateLabel($employee->hire_date)}, para efeitos de obtenção de visto".($embassy ? " junto da {$embassy}" : '').'.',
            $this->salaryFields('obtencao_visto', $data)
        );
    }

    private function aquisicaoResidenciaDeclaration(array $data, Employee $employee, ?array $context): array
    {
        $local = $data['declaration']['residence'] ?? null;

        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Aquisição de Residência',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data)." da instituição desde {$this->dateLabel($employee->hire_date)}, para efeitos de aquisição de residência".($local ? " em {$local}" : '').'.',
            $this->salaryFields('aquisicao_residencia', $data)
        );
    }

    private function adiantamentoSalarioDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Adiantamento de Salário',
            "Declara-se que {$this->subject($data, $employee)} aufere a remuneração abaixo indicada, para efeitos de pedido de adiantamento de salário junto de instituição bancária.",
            $this->salaryFields('adiantamento_salario', $data)
        );
    }

    private function bpcSalarioDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Crédito de Salário (BPC)',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data).' da instituição e aufere a remuneração abaixo indicada, para efeitos de obtenção de crédito de salário junto do BPC.',
            $this->salaryFields('bpc_salario', $data)
        );
    }

    private function consignacaoSalariosDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Consignação de Salários',
            "Declara-se que {$this->subject($data, $employee)} aufere a remuneração abaixo indicada, para efeitos de consignação de salários junto de instituição bancária.",
            $this->salaryFields('consignacao_salarios', $data)
        );
    }

    private function creditoExpressDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Crédito Express',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data).' da instituição, para efeitos de obtenção de crédito express junto de instituição bancária.',
            $this->salaryFields('credito_express', $data)
        );
    }

    private function creditoPessoalDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Crédito Pessoal',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data).' da instituição, para efeitos de obtenção de crédito pessoal junto de instituição bancária.',
            $this->salaryFields('credito_pessoal', $data)
        );
    }

    private function actualizacaoContaBancariaDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Actualização de Conta Bancária',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data).' da instituição, com vencimento processado pela folha de salários, para efeitos de actualização de conta bancária.',
            array_merge($this->salaryFields('actualizacao_conta_bancaria', $data), $this->bankFields($employee))
        );
    }

    private function cartaoDebitoDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Obtenção de Cartão de Débito',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data).' da instituição, para efeitos de obtenção de cartão de débito junto de instituição bancária.',
            $this->salaryFields('cartao_debito', $data)
        );
    }

    private function transferenciaDomiciliacaoDeclaration(array $data, Employee $employee, ?array $context): array
    {
        return $this->buildDeclaration(
            $data,
            $employee,
            $context,
            'Declaração para Transferência/Domiciliação de Salário',
            "Declara-se que {$this->subject($data, $employee)} é ".$this->funcionario($data).' da instituição, para efeitos de transferência/domiciliação de salário junto de instituição bancária.',
            $this->salaryFields('transferencia_domiciliacao_salario', $data)
        );
    }

    private function buildDeclaration(array $data, Employee $employee, ?array $context, string $title, string $statement, array $fields): array
    {
        $declaration = $data['declaration'];
        $dataEmissao = $declaration['issue_date'] ?? now()->toDateString();

        return array_merge($data, [
            'title' => $title,
            'statement' => $statement,
            'fields' => $this->withContextFields($fields, $context),
            'institution' => $context['institution_name'] ?? null,
            'declaration_number' => $declaration['declaration_number'] ?? null,
            'issue_date' => $dataEmissao,
            'issue_date_extenso' => DeclarationText::dateSentence($dataEmissao),
            'signer_role' => $declaration['signer_role'] ?? 'O DIRECTOR',
            'signer_name' => $declaration['signer_name'] ?? '',
            'gender' => $declaration['gender'] ?? $employee->gender,
        ]);
    }

    /**
     * Campos específicos do tipo, preenchidos a partir dos dados do pedido.
     * Os campos salariais são tratados à parte (ver salaryFields).
     */
    private function typeFields(string $code, array $data): array
    {
        $fields = [];

        foreach (config('declaracoes.types.'.$code, []) as $key) {
            if (in_array($key, self::NON_DOCUMENT_FIELDS, true)) {
                continue;
            }

            $value = $data['declaration'][$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $label = config('declaracoes.fields.'.$key.'.label', $key);
            $fields[$label] = $this->formatFieldValue($key, $value);
        }

        return $fields;
    }

    /**
     * Campos salariais do tipo; usa os valores do pedido ou cai para o cálculo automático.
     */
    private function salaryFields(string $code, array $data): array
    {
        $declaration = $data['declaration'];
        $fields = $this->typeFields($code, $data);

        if (empty($declaration['salary_amount'])) {
            return array_merge($fields, $this->remunerationFields($data['remuneration']));
        }

        $tipo = $declaration['salary_type'] ?? 'base';
        $label = $this->salaryLabel($tipo);

        $fields[$label] = $this->money($declaration['salary_amount']);
        $fields[$label.' (por extenso)'] = ucfirst(
            $declaration['salary_words'] ?? NumberToWordsPt::moneyToWords($declaration['salary_amount'])
        );

        if (! empty($declaration['net_salary_amount'])) {
            $fields['Salário líquido'] = $this->money($declaration['net_salary_amount']);
            $fields['Salário líquido (por extenso)'] = ucfirst(
                $declaration['net_salary_words'] ?? NumberToWordsPt::moneyToWords($declaration['net_salary_amount'])
            );
        }

        return $fields;
    }

    private function salaryLabel(string $tipo): string
    {
        return match ($tipo) {
            'liquido' => 'Salário líquido',
            'base_e_liquido' => 'Salário base',
            default => 'Salário',
        };
    }

    private function formatFieldValue(string $key, $value): string
    {
        return match ($key) {
            'salary_amount', 'net_salary_amount' => $this->money($value),
            'salary_words', 'net_salary_words' => ucfirst((string) $value),
            'issue_date', 'admission_date' => DeclarationText::dateLonghand($value) ?? (string) $value,
            default => (string) $value,
        };
    }

    private function subject(array $data, Employee $employee): string
    {
        $declaration = $data['declaration'];
        $gender = DeclarationText::gender($declaration['gender'] ?? $employee->gender);
        $name = mb_strtoupper($declaration['full_name'] ?? $employee->full_name);

        return $gender['tratamento'].' '.$name;
    }

    private function funcionario(array $data): string
    {
        return DeclarationText::funcionario($data['declaration']['gender'] ?? null);
    }

    private function commonFields(array $data, Employee $employee): array
    {
        return [
            'Cargo' => $data['employee']['position'] ?? 'N/A',
            'Departamento' => $data['employee']['department'] ?? 'N/A',
            'Data de admissão' => $this->dateLabel($employee->hire_date),
            'Situação laboral' => $this->statusLabel($employee->status),
        ];
    }

    private function careerFields(array $data): array
    {
        return [
            'Carreira' => $data['employee']['category'] ?? 'N/A',
            'Categoria' => $data['employee']['category'] ?? 'N/A',
            'Tempo na categoria' => $data['career']['time_in_category']['formatted'] ?? 'N/A',
            'Tempo total de serviço' => $data['career']['total_service']['formatted'] ?? 'N/A',
        ];
    }

    private function remunerationFields(array $remuneration): array
    {
        return [
            'Salário base' => $this->money($remuneration['base_salary']),
            'Subsídio de transporte' => $this->money($remuneration['transport_allowance']),
            'Subsídio de alimentação' => $this->money($remuneration['meal_allowance']),
            'Outros rendimentos' => $this->money($remuneration['other_earnings']),
            'Descontos (INSS/IRT)' => $this->money($remuneration['total_deductions']),
            'Vencimento líquido' => $this->money($remuneration['net_pay']),
            'Período de referência' => $remuneration['period_reference'] ?? 'N/A',
        ];
    }

    private function bankFields(Employee $employee): array
    {
        return [
            'Banco' => $employee->bank_name ?? 'N/A',
            'IBAN' => $employee->bank_iban ?? 'N/A',
        ];
    }

    private function withContextFields(array $fields, ?array $context): array
    {
        if (! empty($context['institution_name'])) {
            $fields['Instituição'] = $context['institution_name'];
        }

        if (! empty($context['purpose'])) {
            $fields['Finalidade'] = $context['purpose'];
        }

        return $fields;
    }

    private function extractDeclarationFields(array $context): array
    {
        $declaration = [];

        foreach (DeclarationRequest::FIELD_LIST as $key) {
            if (isset($context[$key]) && $context[$key] !== null && $context[$key] !== '') {
                $declaration[$key] = $context[$key];
            }
        }

        return $declaration;
    }

    /**
     * Preenche valores derivados e dados do funcionário vindos da base de dados.
     * Os valores enviados pelo frontend têm sempre prioridade.
     */
    private function applyDerivedValues(array $data, Employee $employee): array
    {
        $employee->loadMissing(['position', 'department', 'careerCategory']);

        $defaults = config('declaracoes.defaults', []);

        $data['full_name'] = $data['full_name'] ?? $employee->full_name;
        $data['gender'] = $data['gender'] ?? $this->normalizeGender($employee->gender);
        $data['issue_date'] = $data['issue_date'] ?? now()->toDateString();
        $data['declaration_number'] = $data['declaration_number'] ?? $this->generateDeclarationNumber();

        $data['position_category'] = $data['position_category'] ?? $employee->careerCategory?->name ?? $employee->position?->name;
        $data['position'] = $data['position'] ?? $employee->position?->name;
        $data['workplace'] = $data['workplace'] ?? $employee->department?->name;
        $data['employment_bond'] = $data['employment_bond'] ?? $this->contractTypeLabel($employee->contract_type);
        $data['bank'] = $data['bank'] ?? $employee->bank_name;
        $data['account_number'] = $data['account_number'] ?? $employee->bank_iban;
        $data['salary_type'] = $data['salary_type'] ?? 'base';
        $data['salary_amount'] = $data['salary_amount'] ?? $employee->base_salary;
        $data['net_salary_amount'] = $data['net_salary_amount'] ?? $this->netSalary($employee);
        $data['admission_label'] = $data['admission_label'] ?? $this->admissionLabel($employee->hire_date);
        $data['admission_date'] = $data['admission_date'] ?? $employee->hire_date?->toDateString();
        $data['id_card_number'] = $data['id_card_number'] ?? $this->documentNumberLabel($employee);
        $data['phone'] = $data['phone'] ?? $employee->phone;
        $data['email'] = $data['email'] ?? $employee->personal_email;
        $data['address'] = $data['address'] ?? $employee->address;
        $data['salutation'] = $data['salutation'] ?? DeclarationText::gender($data['gender'])['tratamento'];
        $data['service_time'] = $data['service_time'] ?? $this->serviceTimeLabel($employee);
        $data['employer_entity'] = $data['employer_entity'] ?? ($defaults['employer_entity'] ?? null);
        $data['issuing_department'] = $data['issuing_department'] ?? ($defaults['issuing_department'] ?? null);

        if (! empty($data['salary_amount']) && empty($data['salary_words'])) {
            $data['salary_words'] = NumberToWordsPt::moneyToWords($data['salary_amount']);
        }

        if (! empty($data['net_salary_amount']) && empty($data['net_salary_words'])) {
            $data['net_salary_words'] = NumberToWordsPt::moneyToWords($data['net_salary_amount']);
        }

        return $data;
    }

    private function generateDeclarationNumber(): string
    {
        $year = now()->year;
        $suffix = '/GAB-RH/'.$year;

        $max = DeclarationRequest::withTrashed()
            ->whereNotNull('declaration_number')
            ->where('declaration_number', 'like', '%'.$suffix)
            ->pluck('declaration_number')
            ->map(fn ($value) => preg_match('/^(\d+)\/GAB-RH\/\d+$/', (string) $value, $m) ? (int) $m[1] : 0)
            ->max();

        return str_pad(($max ?? 0) + 1, 4, '0', STR_PAD_LEFT).'/GAB-RH/'.$year;
    }

    private function normalizeGender(?string $gender): ?string
    {
        return match (mb_strtolower((string) $gender)) {
            'male', 'masculino', 'm' => 'masculino',
            'female', 'feminino', 'f' => 'feminino',
            default => $gender ?: null,
        };
    }

    private function contractTypeLabel(?string $type): ?string
    {
        return match ($type) {
            'efectivo', 'efetivo', 'indeterminado' => 'Contrato de Trabalho por Tempo Indeterminado',
            'prestacao_servicos', 'prestacao_serviços' => 'Contrato de Prestação de Serviços',
            'determinado', 'temporario' => 'Contrato de Trabalho por Tempo Determinado',
            'estagiario', 'estagiário' => 'Estagiário',
            'comissao', 'comissao_servico' => 'Comissão de Serviço',
            default => $type ?: null,
        };
    }

    private function serviceTimeLabel(Employee $employee): ?string
    {
        if (! $employee->hire_date) {
            return null;
        }

        $diff = $employee->hire_date->diff(now());
        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y.' ano'.($diff->y > 1 ? 's' : '');
        }

        if ($diff->m > 0) {
            $parts[] = $diff->m.' mes'.($diff->m > 1 ? 'es' : '');
        }

        return empty($parts) ? null : 'há '.implode(' e ', $parts);
    }

    private function admissionLabel(?\Illuminate\Support\Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }

        return 'desde '.DeclarationText::monthYear($date);
    }

    private function documentNumberLabel(Employee $employee): ?string
    {
        if (empty($employee->document_type) || empty($employee->document_number)) {
            return null;
        }

        $tipo = mb_strtolower($employee->document_type);

        return (str_contains($tipo, 'bi') || str_contains($tipo, 'bilhete'))
            ? $employee->document_number
            : null;
    }

    private function netSalary(Employee $employee): ?float
    {
        $payslip = Payslip::where('employee_id', $employee->id)
            ->latest('generated_at')
            ->first();

        return $payslip?->net_pay;
    }

    private function requestFields(DeclarationRequest $request): array
    {
        return $request->only(array_merge(
            [
                'purpose',
                'institution_name',
                'institution_type',
                'additional_info',
            ],
            DeclarationRequest::FIELD_LIST
        ));
    }

    private function employeeData(Employee $employee): array
    {
        return [
            'full_name' => $employee->full_name,
            'document_type' => $employee->document_type,
            'document_number' => $employee->document_number,
            'nif' => $employee->nif,
            'date_of_birth' => $employee->date_of_birth?->format('Y-m-d'),
            'gender' => $employee->gender,
            'marital_status' => $employee->marital_status,
            'position' => $employee->position?->name,
            'department' => $employee->department?->name,
            'category' => $employee->careerCategory?->name,
            'status' => $employee->status,
            'status_label' => $this->statusLabel($employee->status),
        ];
    }

    private function remunerationData(Employee $employee, ?Payslip $payslip): array
    {
        if (! $payslip) {
            return [
                'base_salary' => $employee->base_salary ?? 0,
                'transport_allowance' => 0,
                'meal_allowance' => 0,
                'overtime' => 0,
                'other_earnings' => 0,
                'gross_pay' => $employee->base_salary ?? 0,
                'total_deductions' => 0,
                'net_pay' => $employee->base_salary ?? 0,
                'period_reference' => null,
            ];
        }

        return [
            'base_salary' => $payslip->base_salary,
            'transport_allowance' => $payslip->transport_allowance,
            'meal_allowance' => $payslip->meal_allowance,
            'overtime' => $payslip->overtime,
            'other_earnings' => $payslip->other_earnings,
            'gross_pay' => $payslip->gross_pay,
            'total_deductions' => $payslip->total_deductions,
            'net_pay' => $payslip->net_pay,
            'period_reference' => $payslip->period?->name,
        ];
    }

    private function findType(int $typeId)
    {
        $type = \App\Models\RH\Declaration\DeclarationType::find($typeId);
        if (! $type) {
            throw new ModelNotFoundException('Tipo de declaração não encontrado.');
        }

        return $type;
    }

    private function findEmployee(int $employeeId): Employee
    {
        $employee = Employee::find($employeeId);
        if (! $employee) {
            throw new ModelNotFoundException('Funcionário não encontrado.');
        }

        return $employee;
    }

    private function notifyApprovers(DeclarationRequest $request): void
    {
        $employee = $request->employee;
        $department = $employee?->department;
        $notifiables = [];

        if ($department && $department->responsible) {
            $notifiables[] = $department->responsible;
        }

        if (! empty($notifiables)) {
            Notification::send($notifiables, new DeclarationRequestNotification($request, 'submitted'));
        }
    }

    private function notifyEmployee(DeclarationRequest $request, string $action, ?string $comment): void
    {
        $user = $request->employee?->user;
        if (! $user) {
            return;
        }

        Notification::send($user, new DeclarationRequestNotification($request, $action, $comment));
    }

    private function generateIssuedNumber(DeclarationRequest $request): string
    {
        $typeCode = strtoupper(substr($request->declarationType?->code ?? 'DEC', 0, 4));

        return $typeCode.'-'.$request->reference_number;
    }

    private function dateLabel(?\Illuminate\Support\Carbon $date): ?string
    {
        return $date?->format('d/m/Y');
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'on_leave' => 'De licença',
            'suspended' => 'Suspenso',
            'retired' => 'Reformado',
            'terminated' => 'Rescindido',
            default => $status ?? 'N/A',
        };
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, ',', '.').' Kz';
    }
}
