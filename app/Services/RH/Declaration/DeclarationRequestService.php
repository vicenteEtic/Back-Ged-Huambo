<?php

namespace App\Services\RH\Declaration;

use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Career\RetirementEligibility;
use App\Models\RH\Declaration\DeclarationRequest;
use App\Models\RH\Disciplinary\DisciplinaryRecord;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Payroll\Payslip;
use App\Notifications\RH\DeclarationRequestNotification;
use App\Repositories\RH\Declaration\DeclarationRequestRepository;
use App\Services\AbstractService;
use App\Services\RH\Career\CareerService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DeclarationRequestService extends AbstractService
{
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

            $data['status'] = $declarationType->requires_approval ? 'pending' : 'approved';
            $data['content'] = $this->generateContent($declarationType->code, $employee);

            $declarationRequest = $this->store($data);

            if ($declarationRequest->status === 'pending') {
                $this->notifyApprovers($declarationRequest);
            }

            return $declarationRequest->fresh(['employee', 'declarationType', 'approvedBy', 'issuedBy']);
        });
    }

    public function preview(int $typeId, int $employeeId): array
    {
        $declarationType = $this->findType($typeId);
        $employee = $this->findEmployee($employeeId);

        return [
            'declaration_type' => [
                'id' => $declarationType->id,
                'code' => $declarationType->code,
                'name' => $declarationType->name,
                'description' => $declarationType->description,
            ],
            'content' => $this->generateContent($declarationType->code, $employee),
        ];
    }

    public function previewRequest(int $id): array
    {
        $request = DeclarationRequest::with('employee', 'declarationType')->findOrFail($id);

        return [
            'reference_number' => $request->reference_number,
            'declaration_type' => $request->declarationType?->name,
            'content' => $this->generateContent($request->declarationType->code, $request->employee),
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

            $request->update([
                'content' => $this->generateContent($request->declarationType->code, $request->employee),
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

    public function generateContent(string $code, Employee $employee): array
    {
        $employee->load(['position', 'department', 'careerCategory', 'careerRegime']);
        $career = $this->careerService->calculate($employee);
        $payslip = Payslip::where('employee_id', $employee->id)
            ->latest('generated_at')
            ->first();

        $data = [
            'type' => $code,
            'generated_at' => now()->toDateTimeString(),
            'employee' => $this->employeeData($employee),
            'career' => $career,
            'remuneration' => $this->remunerationData($employee, $payslip),
        ];

        return match ($code) {
            'servico' => $this->serviceDeclaration($data, $employee),
            'vinculo_laboral' => $this->laborLinkDeclaration($data, $employee),
            'tempo_servico' => $this->serviceTimeDeclaration($data, $employee),
            'vencimento' => $this->remunerationDeclaration($data),
            'efetividade' => $this->effectivenessDeclaration($data, $employee),
            'ausencia_disciplinar' => $this->noDisciplinaryDeclaration($data, $employee),
            'frequencia' => $this->attendanceDeclaration($data, $employee),
            'aposentacao' => $this->retirementDeclaration($data, $employee),
            'compatibilidade' => $this->compatibilityDeclaration($data, $employee),
            'situacao_funcional' => $this->functionalStatusDeclaration($data, $employee),
            default => $data,
        };
    }

    private function serviceDeclaration(array $data, Employee $employee): array
    {
        return array_merge($data, [
            'title' => 'Declaração de Serviço',
            'statement' => "Declara-se para os devidos efeitos que {$employee->full_name} exerce funções na instituição desde {$this->dateLabel($employee->hire_date)}.",
            'fields' => [
                'Categoria/Cargo' => $data['employee']['position'] ?? 'N/A',
                'Departamento' => $data['employee']['department'] ?? 'N/A',
                'Data de ingresso' => $this->dateLabel($employee->hire_date),
                'Situação funcional' => $this->statusLabel($employee->status),
            ],
        ]);
    }

    private function laborLinkDeclaration(array $data, Employee $employee): array
    {
        return array_merge($data, [
            'title' => 'Declaração de Vínculo Laboral',
            'statement' => "Declara-se que {$employee->full_name} mantém vínculo laboral de emprego público com a instituição.",
            'fields' => [
                'Tipo de vínculo' => $employee->contract_type ?? 'Emprego Público',
                'Data de efetivação' => $this->dateLabel($employee->effective_date ?? $employee->hire_date),
                'Situação' => $this->statusLabel($employee->status),
            ],
        ]);
    }

    private function serviceTimeDeclaration(array $data, Employee $employee): array
    {
        return array_merge($data, [
            'title' => 'Declaração de Tempo de Serviço',
            'statement' => "Declara-se que {$employee->full_name} esteve ao serviço do Estado desde {$this->dateLabel($employee->institution_entry_date ?? $employee->hire_date)}.",
            'fields' => [
                'Data de entrada' => $this->dateLabel($employee->institution_entry_date ?? $employee->hire_date),
                'Tempo de serviço acumulado' => $data['career']['total_service']['formatted'] ?? 'N/A',
                'Tempo na categoria' => $data['career']['time_in_category']['formatted'] ?? 'N/A',
                'Carreira' => $data['employee']['category'] ?? 'N/A',
                'Categoria' => $data['employee']['category'] ?? 'N/A',
            ],
        ]);
    }

    private function remunerationDeclaration(array $data): array
    {
        $remuneration = $data['remuneration'];

        return array_merge($data, [
            'title' => 'Declaração de Vencimento ou Remuneração',
            'statement' => 'Declara-se a remuneração auferida pelo funcionário.',
            'fields' => [
                'Salário base' => $this->money($remuneration['base_salary']),
                'Subsídio de transporte' => $this->money($remuneration['transport_allowance']),
                'Subsídio de alimentação' => $this->money($remuneration['meal_allowance']),
                'Outros rendimentos' => $this->money($remuneration['other_earnings']),
                'Descontos (INSS/IRT)' => $this->money($remuneration['total_deductions']),
                'Remuneração líquida' => $this->money($remuneration['net_pay']),
                'Período de referência' => $remuneration['period_reference'] ?? 'N/A',
            ],
        ]);
    }

    private function effectivenessDeclaration(array $data, Employee $employee): array
    {
        return array_merge($data, [
            'title' => 'Declaração de Efetividade',
            'statement' => "Declara-se que {$employee->full_name} se encontra efetivamente em exercício de funções na instituição.",
            'fields' => [
                'Situação' => $this->statusLabel($employee->status),
                'Cargo' => $data['employee']['position'] ?? 'N/A',
                'Departamento' => $data['employee']['department'] ?? 'N/A',
                'Data de admissão' => $this->dateLabel($employee->hire_date),
            ],
        ]);
    }

    private function noDisciplinaryDeclaration(array $data, Employee $employee): array
    {
        $records = DisciplinaryRecord::where('employee_id', $employee->id)->count();

        return array_merge($data, [
            'title' => 'Declaração de Ausência de Processo Disciplinar',
            'statement' => $records > 0
                ? "Declara-se que {$employee->full_name} possui registos disciplinares na instituição."
                : "Declara-se que {$employee->full_name} não possui processo disciplinar pendente ou registado na instituição.",
            'fields' => [
                'Processos disciplinares' => $records,
                'Conclusão' => $records > 0 ? 'Possui registos' : 'Sem registos',
            ],
        ]);
    }

    private function attendanceDeclaration(array $data, Employee $employee): array
    {
        $start = Carbon::today()->subDays(89)->startOfDay();
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->get();

        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $late = $attendances->where('late_minutes', '>', 0)->count();

        return array_merge($data, [
            'title' => 'Declaração de Frequência/Presença',
            'statement' => "Declara-se que {$employee->full_name} comparece regularmente ao serviço.",
            'fields' => [
                'Período de referência' => "Últimos 90 dias (desde {$start->format('d/m/Y')})",
                'Dias de presença' => $present,
                'Dias de ausência' => $absent,
                'Dias com atraso' => $late,
            ],
        ]);
    }

    private function retirementDeclaration(array $data, Employee $employee): array
    {
        $eligibility = RetirementEligibility::where('employee_id', $employee->id)->latest('id')->first();

        return array_merge($data, [
            'title' => 'Declaração para Efeitos de Aposentação',
            'statement' => "Declara-se o histórico funcional e de contribuições de {$employee->full_name} para efeitos de aposentação.",
            'fields' => [
                'Tempo de serviço' => $data['career']['total_service']['formatted'] ?? 'N/A',
                'Categoria' => $data['employee']['category'] ?? 'N/A',
                'Anos de contribuição' => $eligibility ? (string) $eligibility->contribution_years : 'N/A',
                'Idade de reforma' => $eligibility ? (string) $eligibility->retirement_age : 'N/A',
                'Data esperada de aposentação' => $eligibility?->expected_retirement_date?->format('Y-m-d') ?? 'N/A',
                'Histórico funcional' => $employee->functionalHistory()->count() . ' registo(s)',
            ],
        ]);
    }

    private function compatibilityDeclaration(array $data, Employee $employee): array
    {
        return array_merge($data, [
            'title' => 'Declaração de Compatibilidade ou Acumulação de Funções',
            'statement' => "Declara-se a situação de compatibilidade ou acumulação de funções de {$employee->full_name}.",
            'fields' => [
                'Cargo na instituição' => $data['employee']['position'] ?? 'N/A',
                'Departamento' => $data['employee']['department'] ?? 'N/A',
                'Regime de carreira' => $data['employee']['career_regime'] ?? 'N/A',
                'Situação' => $this->statusLabel($employee->status),
            ],
        ]);
    }

    private function functionalStatusDeclaration(array $data, Employee $employee): array
    {
        return array_merge($data, [
            'title' => 'Declaração de Situação Funcional',
            'statement' => "Declara-se a situação funcional atual de {$employee->full_name}.",
            'fields' => [
                'Categoria' => $data['employee']['category'] ?? 'N/A',
                'Cargo' => $data['employee']['position'] ?? 'N/A',
                'Local de trabalho' => $data['employee']['department'] ?? 'N/A',
                'Estado do vínculo' => $this->statusLabel($employee->status),
                'Progressão na carreira' => $data['career']['time_in_category']['formatted'] ?? 'N/A',
                'Data de admissão' => $this->dateLabel($employee->hire_date),
            ],
        ]);
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
            'career_regime' => $employee->careerRegime?->name,
            'status' => $employee->status,
            'status_label' => $this->statusLabel($employee->status),
        ];
    }

    private function remunerationData(Employee $employee, ?Payslip $payslip): array
    {
        if (!$payslip) {
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
        if (!$type) {
            throw new ModelNotFoundException('Tipo de declaração não encontrado.');
        }
        return $type;
    }

    private function findEmployee(int $employeeId): Employee
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
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

        if (!empty($notifiables)) {
            Notification::send($notifiables, new DeclarationRequestNotification($request, 'submitted'));
        }
    }

    private function notifyEmployee(DeclarationRequest $request, string $action, ?string $comment): void
    {
        $user = $request->employee?->user;
        if (!$user) {
            return;
        }

        Notification::send($user, new DeclarationRequestNotification($request, $action, $comment));
    }

    private function generateIssuedNumber(DeclarationRequest $request): string
    {
        $typeCode = strtoupper(substr($request->declarationType?->code ?? 'DEC', 0, 4));
        return $typeCode . '-' . $request->reference_number;
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
        return number_format((float) ($value ?? 0), 2, ',', '.') . ' Kz';
    }
}
