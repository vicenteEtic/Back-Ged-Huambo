<?php

namespace App\Services\RH\Leave;

use App\Models\RH\Leave\LeaveRequest;
use App\Models\RH\Leave\LeaveType;
use App\Notifications\RH\LeaveRequestSubmittedNotification;
use App\Repositories\RH\Leave\LeaveRequestRepository;
use App\Services\AbstractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class LeaveRequestService extends AbstractService
{
    public function __construct(
        LeaveRequestRepository $repository,
        protected LeavePlanService $planService,
        protected LeaveApprovalService $approvalService,
        protected LeaveEntitlementService $entitlementService,
        protected HolidayService $holidayService,
    ) {
        parent::__construct($repository);
    }

    public function update(array $data, int $id): LeaveRequest
    {
        return DB::transaction(function () use ($data, $id) {
            $leaveRequest = LeaveRequest::with('leavePlan')->findOrFail($id);
            $oldPlanId = $leaveRequest->leave_plan_id;

            $hasDays = isset($data['days']);

            if ($hasDays) {
                $start = $data['start_date'] ?? $leaveRequest->start_date->format('Y-m-d');
                $data['end_date'] = $this->calculateReturnByDays($start, $data['days'])['end_date'];
            }
            unset($data['days']);

            $datesChanged = isset($data['start_date']) || array_key_exists('end_date', $data);

            if ($datesChanged) {
                $start = $data['start_date'] ?? $leaveRequest->start_date->format('Y-m-d');
                $end = array_key_exists('end_date', $data)
                    ? ($data['end_date'] !== null ? $data['end_date'] : null)
                    : $leaveRequest->end_date?->format('Y-m-d');
                $employeeId = $data['employee_id'] ?? $leaveRequest->employee_id;

                if ($end !== null) {
                    $data['total_days'] = $this->calculateBusinessDays($start, $end);
                    $data['return_date'] = $this->calculateReturnDate($end);
                    $data['end_date'] = $end;
                    $this->checkDateConflict($employeeId, $start, $end, $leaveRequest->id);
                } else {
                    $data['total_days'] = null;
                    $data['return_date'] = null;
                    $data['end_date'] = null;
                    $this->checkIndefiniteConflict($employeeId, $start, $leaveRequest->id);
                }
            }

            if (isset($data['start_date'])) {
                $year = Carbon::parse($data['start_date'])->year;
                $plan = $this->planService->findOrCreateForRequest(
                    $leaveRequest->employee_id,
                    $year,
                    $leaveRequest->leave_type_id
                );
                $this->planService->syncBalance($plan->id);
                $data['leave_plan_id'] = $plan->id;
            }

            $updated = $this->repository->update($data, $id);

            if ($oldPlanId && $oldPlanId !== ($data['leave_plan_id'] ?? $oldPlanId)) {
                $this->planService->syncBalance($oldPlanId);
            }

            return $updated->fresh(['employee', 'leaveType', 'leavePlan', 'approvals']);
        });
    }

    public function submit(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $hasDays = isset($data['days']);
            $hasEndDate = isset($data['end_date']) && $data['end_date'] !== null;
            $isIndefinite = ! $hasDays && ! $hasEndDate;

            if ($hasDays) {
                $data['end_date'] = $this->calculateReturnByDays($data['start_date'], $data['days'])['end_date'];
            }
            unset($data['days']);

            if ($isIndefinite) {
                $data['end_date'] = null;
                $data['total_days'] = null;
                $data['return_date'] = null;
            } else {
                $data['total_days'] = $this->calculateBusinessDays($data['start_date'], $data['end_date']);
                $data['return_date'] = $this->calculateReturnDate($data['end_date']);
            }

            $data['status'] = 'pending';

            $this->assertCanTakeAdmissionYearLeave($data['employee_id'], $data['leave_type_id']);

            if ($isIndefinite) {
                $this->checkIndefiniteConflict($data['employee_id'], $data['start_date']);
            } else {
                $this->checkDateConflict(
                    $data['employee_id'],
                    $data['start_date'],
                    $data['end_date']
                );
            }

            $year = Carbon::parse($data['start_date'])->year;
            $plan = $this->planService->findOrCreateForRequest(
                $data['employee_id'],
                $year,
                $data['leave_type_id']
            );
            $data['leave_plan_id'] = $plan->id;

            if (! $isIndefinite) {
                $this->planService->syncBalance($plan->id);
                $plan->refresh();

                $remaining = max(0, $plan->total_days_entitled - $plan->days_used - $plan->days_pending);
                if ($data['total_days'] > $remaining) {
                    $typeName = $plan->leaveType?->name ?? 'esta licença';
                    $yearsOfService = $this->entitlementService->yearsOfService($plan->employee);
                    throw new \DomainException(
                        "Saldo insuficiente de {$typeName} para {$year}. ".
                        "Tempo de serviço: {$this->formatServiceTime($yearsOfService)}. ".
                        "Disponível: {$remaining} dia(s), solicitado: {$data['total_days']} dia(s)."
                    );
                }
            }

            $leaveRequest = $this->store($data);
            $this->planService->syncBalance($data['leave_plan_id']);

            $this->notifyApprovers($leaveRequest);

            return $leaveRequest->fresh(['employee', 'leaveType', 'leavePlan', 'approvals']);
        });
    }

    /**
     * Prorrogação: cria uma nova licença encadeada a uma existente.
     *
     * Regras (independentes de tempo indeterminado):
     * - A licença original tem de estar aprovada;
     * - O tipo de licença tem de permitir prorrogação (allows_extension);
     * - A licença original tem de ter end_date definido (não pode ser indeterminada);
     * - A prorrogação só pode ser pedida enquanto a licença estiver vigente;
     * - Respeita o nº máximo de prorrogações configurado (max_extensions).
     */
    public function extend(array $data, int $id): LeaveRequest
    {
        return DB::transaction(function () use ($data, $id) {
            $original = LeaveRequest::findOrFail($id);

            if ($original->status !== 'approved') {
                throw new \DomainException('Apenas licenças aprovadas podem ser prorrogadas.');
            }

            if ($original->end_date === null) {
                throw new \DomainException('Licenças de tempo indeterminado não podem ser prorrogadas — não têm data de término.');
            }

            $leaveType = LeaveType::find($original->leave_type_id);

            if (! $leaveType || ! $leaveType->allows_extension) {
                throw new \DomainException('Este tipo de licença não permite prorrogação.');
            }

            $today = now()->toDateString();
            if ($original->end_date->toDateString() < $today) {
                throw new \DomainException(
                    'O período da licença já terminou ('.$original->end_date->format('d/m/Y').'): não é possível prorrogar. A prorrogação só pode ser solicitada enquanto a licença estiver vigente.'
                );
            }

            $max = $leaveType->max_extensions;
            if ($max !== null && $original->extension_count >= $max) {
                throw new \DomainException(
                    'Foi atingido o número máximo de prorrogações ('.$max.') para este tipo de licença.'
                );
            }

            $extensionDays = $leaveType->extension_days ?? 1;

            $startDate = Carbon::parse($original->end_date)->addDay()->toDateString();
            $endDate = Carbon::parse($startDate)->addDays($extensionDays - 1)->toDateString();

            $totalDays = $this->calculateBusinessDays($startDate, $endDate);
            $returnDate = $this->calculateReturnDate($endDate);

            $this->checkDateConflict($original->employee_id, $startDate, $endDate);

            $year = Carbon::parse($startDate)->year;
            $plan = $this->planService->findOrCreateForRequest(
                $original->employee_id,
                $year,
                $original->leave_type_id
            );
            $this->planService->syncBalance($plan->id);

            $extended = $this->store([
                'employee_id' => $original->employee_id,
                'leave_type_id' => $original->leave_type_id,
                'leave_plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'return_date' => $returnDate,
                'reason' => $data['reason'] ?? 'Prorrogação da licença #'.$original->id,
                'status' => 'pending',
                'extends_request_id' => $original->id,
                'extension_count' => $original->extension_count + 1,
            ]);

            $this->updateOriginalExtensionCount($original);

            $this->planService->syncBalance($plan->id);

            $this->notifyApprovers($extended);

            return $extended->fresh(['employee', 'leaveType', 'leavePlan', 'extendsRequest', 'approvals']);
        });
    }

    private function updateOriginalExtensionCount(LeaveRequest $original): void
    {
        $original->update([
            'extension_count' => $original->extension_count + 1,
        ]);
    }

    private function notifyApprovers(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;
        $department = $employee->department;

        $notifiables = [];

        if ($department && $department->responsible?->user) {
            $notifiables[] = $department->responsible->user;
        }

        if (! empty($notifiables)) {
            Notification::send($notifiables, new LeaveRequestSubmittedNotification($leaveRequest));
        }
    }

    private function assertCanTakeAdmissionYearLeave(int $employeeId, int $leaveTypeId): void
    {
        $leaveType = LeaveType::find($leaveTypeId);

        if (! $leaveType?->service_years_based) {
            return;
        }

        $employee = \App\Models\RH\Employee\Employee::findOrFail($employeeId);

        if (! $this->entitlementService->hasCompletedMinimumService($employee)) {
            throw new \DomainException(
                'Férias do ano de admissão só podem ser gozadas após 6 meses de trabalho efectivo (art. 77.º n.º 3 da Lei 26/22).'
            );
        }
    }

    /**
     * Conflito de datas para licenças com end_date definido.
     * Considera também licenças de tempo indeterminado activas que sejam
     * anteriores ou coincidentes com o período proposto.
     */
    private function checkDateConflict(int $employeeId, string $startDate, string $endDate, ?int $ignoreId = null): void
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $conflict = LeaveRequest::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($start, $end) {
                // Licença finita sobreposta ao período pedido
                $q->where(function ($q2) use ($start, $end) {
                    $q2->whereNotNull('end_date')
                        ->whereDate('start_date', '<=', $end)
                        ->whereDate('end_date', '>=', $start);
                })
                // Licença de tempo indeterminado já iniciada (ou a iniciar dentro do período)
                ->orWhere(function ($q2) use ($end) {
                    $q2->whereNull('end_date')
                        ->whereDate('start_date', '<=', $end);
                });
            })
            ->first();

        if ($conflict) {
            $typeName = $conflict->leaveType?->name ?? 'férias';
            $status = $conflict->status === 'approved' ? 'aprovadas' : 'em aprovação';
            $range = $conflict->end_date
                ? "entre {$conflict->start_date->format('d/m/Y')} e {$conflict->end_date->format('d/m/Y')}"
                : "de tempo indeterminado desde {$conflict->start_date->format('d/m/Y')}";
            throw new \DomainException(
                "Conflito de datas: o funcionário já tem {$typeName} {$status} {$range}."
            );
        }
    }

    /**
     * Conflito para licenças de tempo indeterminado (end_date = null).
     * Uma licença indefinida recobre todas as datas a partir do seu início,
     * logo entra em conflito com qualquer outra licença activa que:
     * - já esteja em curso (indefinida anterior ou finita que cobre o início);
     * - comece durante a licença indefinida (start_date >= novo início).
     */
    private function checkIndefiniteConflict(int $employeeId, string $startDate, ?int $ignoreId = null): void
    {
        $start = Carbon::parse($startDate);

        $conflict = LeaveRequest::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($start) {
                // Licença activa que começa depois do início da indefinida
                $q->whereDate('start_date', '>=', $start)
                    ->orWhere(function ($q2) use ($start) {
                        // Licença já em curso que abrange o início proposto
                        $q2->whereDate('start_date', '<=', $start)
                            ->where(function ($q3) use ($start) {
                                $q3->whereNull('end_date')
                                    ->orWhereDate('end_date', '>=', $start);
                            });
                    });
            })
            ->first();

        if ($conflict) {
            $typeName = $conflict->leaveType?->name ?? 'licença';
            $status = $conflict->status === 'approved' ? 'aprovada' : 'em aprovação';
            $range = $conflict->end_date
                ? "entre {$conflict->start_date->format('d/m/Y')} e {$conflict->end_date->format('d/m/Y')}"
                : "de tempo indeterminado desde {$conflict->start_date->format('d/m/Y')}";
            throw new \DomainException(
                "Conflito: o funcionário já tem {$typeName} {$status} {$range}. Não é possível iniciar uma licença de tempo indeterminado."
            );
        }
    }

    /**
     * Verifica se o funcionário está de licença (aprovada) na data indicada.
     * Licenças de tempo indeterminado (end_date = null) são consideradas activas
     * enquanto a data for >= start_date.
     */
    public function isOnLeave(int $employeeId, string $date): bool
    {
        return LeaveRequest::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->exists();
    }

    public function calculateBusinessDays(string $start, string $end): int
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $days = 0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if ($d->isWeekday() && ! $this->holidayService->isHoliday($d)) {
                $days++;
            }
        }

        return $days;
    }

    public function calculateReturnDate(string $endDate): string
    {
        $day = Carbon::parse($endDate)->addDay();

        while ($day->isWeekend() || $this->holidayService->isHoliday($day)) {
            $day->addDay();
        }

        return $day->format('Y-m-d');
    }

    public function calculateReturnByDays(string $startDate, int $days): array
    {
        $start = Carbon::parse($startDate);
        $current = $start->copy();
        $counted = 0;

        while ($counted < $days) {
            if ($current->isWeekday() && ! $this->holidayService->isHoliday($current)) {
                $counted++;
            }
            $current->addDay();
        }

        $endDate = $current->copy()->subDay();
        $holidays = $this->holidayService->holidaysBetween($start, $endDate);

        return [
            'start_date' => $start->format('Y-m-d'),
            'days' => $days,
            'end_date' => $endDate->format('Y-m-d'),
            'return_date' => $this->calculateReturnDate($endDate->format('Y-m-d')),
            'holidays_count' => count($holidays),
            'holidays' => $holidays,
            'calendar_days' => $start->diffInDays($endDate) + 1,
        ];
    }

    public function annualEntitlement(int $employeeId, ?int $leaveTypeId = null): array
    {
        $employee = \App\Models\RH\Employee\Employee::findOrFail($employeeId);

        return $this->entitlementService->annualEntitlement($employee, $leaveTypeId);
    }

    public function balanceByEmployee(int $employeeId, int $year, ?int $leaveTypeId = null): array
    {
        $employee = \App\Models\RH\Employee\Employee::findOrFail($employeeId);
        $yearsOfService = $this->entitlementService->yearsOfService($employee);
        $serviceTime = $this->entitlementService->serviceTimeParts($yearsOfService);
        if ($leaveTypeId) {
            $plan = $this->planService->findOrCreateForRequest($employeeId, $year, $leaveTypeId);
            $this->planService->syncBalance($plan->id);
            $plan->refresh();

            return [
                'employee_id' => $employeeId,
                'year' => $year,
                'leave_type_id' => $leaveTypeId,
                'leave_type_name' => $plan->leaveType?->name,
                'total_days_entitled' => $plan->total_days_entitled,
                'days_used' => $plan->days_used,
                'days_pending' => $plan->days_pending,
                'days_remaining' => $plan->days_remaining,
                'years_of_service' => $serviceTime['years'],
                'months_of_service' => $serviceTime['months'],
            ];
        }

        $plans = \App\Models\RH\Leave\LeavePlan::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->get();

        $result = [];
        foreach ($plans as $plan) {
            $this->planService->syncBalance($plan->id);
            $plan->refresh();
            $result[] = [
                'leave_type_id' => $plan->leave_type_id,
                'leave_type_name' => $plan->leaveType?->name,
                'total_days_entitled' => $plan->total_days_entitled,
                'days_used' => $plan->days_used,
                'days_pending' => $plan->days_pending,
                'days_remaining' => $plan->days_remaining,
            ];
        }

        return [
            'employee_id' => $employeeId,
            'year' => $year,
            'years_of_service' => $serviceTime['years'],
            'months_of_service' => $serviceTime['months'],
            'balances' => $result,
        ];
    }

    private function formatServiceTime(float $years): string
    {
        if ($years < 1) {
            $months = max(1, (int) round($years * 12));

            return "{$months} mês(es)";
        }

        $wholeYears = (int) floor($years);
        $months = (int) round(($years - $wholeYears) * 12);
        $parts = [];

        if ($wholeYears > 0) {
            $parts[] = $wholeYears.' ano(s)';
        }
        if ($months > 0) {
            $parts[] = $months.' mês(es)';
        }

        return implode(' e ', $parts);
    }
}
