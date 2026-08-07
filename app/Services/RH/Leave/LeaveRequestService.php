<?php

namespace App\Services\RH\Leave;

use App\Models\RH\Leave\LeaveRequest;
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

            if (isset($data['start_date']) || isset($data['end_date'])) {
                $start = $data['start_date'] ?? $leaveRequest->start_date->format('Y-m-d');
                $end = $data['end_date'] ?? $leaveRequest->end_date->format('Y-m-d');
                $employeeId = $data['employee_id'] ?? $leaveRequest->employee_id;

                $data['total_days'] = $this->calculateBusinessDays($start, $end);
                $data['return_date'] = $this->calculateReturnDate($end);
                $this->checkDateConflict($employeeId, $start, $end, $leaveRequest->id);
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
            $data['total_days'] = $this->calculateBusinessDays($data['start_date'], $data['end_date']);
            $data['return_date'] = $this->calculateReturnDate($data['end_date']);
            $data['status'] = 'pending';

            $this->assertCanTakeAdmissionYearLeave($data['employee_id'], $data['leave_type_id']);

            $this->checkDateConflict(
                $data['employee_id'],
                $data['start_date'],
                $data['end_date']
            );

            $year = Carbon::parse($data['start_date'])->year;
            $plan = $this->planService->findOrCreateForRequest(
                $data['employee_id'],
                $year,
                $data['leave_type_id']
            );
            $data['leave_plan_id'] = $plan->id;

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

            $leaveRequest = $this->store($data);
            $this->planService->syncBalance($data['leave_plan_id']);

            // Notify department head
            $this->notifyApprovers($leaveRequest);

            return $leaveRequest->fresh(['employee', 'leaveType', 'leavePlan', 'approvals']);
        });
    }

    private function notifyApprovers(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;
        $department = $employee->department;

        $notifiables = [];

        if ($department && $department->responsible) {
            $notifiables[] = $department->responsible;
        }

        if (! empty($notifiables)) {
            Notification::send($notifiables, new LeaveRequestSubmittedNotification($leaveRequest));
        }
    }

    private function assertCanTakeAdmissionYearLeave(int $employeeId, int $leaveTypeId): void
    {
        $leaveType = \App\Models\RH\Leave\LeaveType::find($leaveTypeId);

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

    private function checkDateConflict(int $employeeId, string $startDate, string $endDate, ?int $ignoreId = null): void
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $conflict = LeaveRequest::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            })
            ->first();

        if ($conflict) {
            $typeName = $conflict->leaveType?->name ?? 'férias';
            $status = $conflict->status === 'approved' ? 'aprovadas' : 'em aprovação';
            throw new \DomainException(
                "Conflito de datas: o funcionário já tem {$typeName} {$status} entre {$conflict->start_date->format('d/m/Y')} e {$conflict->end_date->format('d/m/Y')}."
            );
        }
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

    public function annualEntitlement(int $employeeId): array
    {
        $employee = \App\Models\RH\Employee\Employee::findOrFail($employeeId);

        return $this->entitlementService->annualEntitlement($employee);
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
