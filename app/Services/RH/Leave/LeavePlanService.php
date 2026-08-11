<?php

namespace App\Services\RH\Leave;

use App\Models\RH\Leave\LeavePlan;
use App\Repositories\RH\Leave\LeavePlanRepository;
use App\Services\AbstractService;

class LeavePlanService extends AbstractService
{
    public function __construct(
        LeavePlanRepository $repository,
        protected LeaveEntitlementService $entitlementService,
    ) {
        parent::__construct($repository);
    }

    public function store(array $data): \App\Models\RH\Leave\LeavePlan
    {
        $data = $this->clean($data);

        $data = $this->resolveEntitlement($data);

        $existing = LeavePlan::where('employee_id', $data['employee_id'])
            ->where('year', $data['year'])
            ->where('leave_type_id', $data['leave_type_id'] ?? null)
            ->first();

        if ($existing && array_key_exists('expected_month', $data) && $existing->expected_month !== $data['expected_month']) {
            $data['upcoming_notified_at'] = null;
        }

        return LeavePlan::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'year' => $data['year'], 'leave_type_id' => $data['leave_type_id'] ?? null],
            $data
        )->fresh();
    }

    public function syncBalance(int $planId): LeavePlan
    {
        $plan = LeavePlan::with('leaveRequests')->findOrFail($planId);
        $query = $plan->leaveRequests()->where('status', 'approved');
        $pendingQuery = $plan->leaveRequests()->where('status', 'pending');

        $plan->days_used = round($query->sum('total_days'), 1);
        $plan->days_pending = round($pendingQuery->sum('total_days'), 1);
        $plan->save();
        return $plan->fresh();
    }

    public function findOrCreateForRequest(int $employeeId, int $year, int $leaveTypeId): LeavePlan
    {
        $leaveType = \App\Models\RH\Leave\LeaveType::findOrFail($leaveTypeId);
        $employee = \App\Models\RH\Employee\Employee::findOrFail($employeeId);

        return LeavePlan::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'year' => $year,
                'leave_type_id' => $leaveTypeId,
            ],
            ['total_days_entitled' => $this->entitlementService->entitledDays($employee, $leaveType)]
        );
    }

    public function calendar(int $year, ?int $departmentId = null): array
    {
        $query = \App\Models\RH\Leave\LeaveRequest::with(['employee', 'leaveType'])
            ->whereYear('start_date', $year)
            ->whereIn('status', ['approved', 'pending']);

        if ($departmentId) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        return $query->get()->map(fn($r) => [
            'id' => $r->id,
            'employee' => $r->employee?->full_name,
            'type' => $r->leaveType?->name,
            'start_date' => $r->start_date->format('Y-m-d'),
            'end_date' => $r->end_date->format('Y-m-d'),
            'total_days' => $r->total_days,
            'status' => $r->status,
        ])->toArray();
    }

    /**
     * Calcula os dias a que o funcionário tem direito quando o plano é de férias
     * anuais (service_years_based) e o total não foi fornecido manualmente.
     */
    private function resolveEntitlement(array $data): array
    {
        $leaveTypeId = $data['leave_type_id'] ?? null;

        if (!$leaveTypeId || array_key_exists('total_days_entitled', $data)) {
            return $data;
        }

        $leaveType = \App\Models\RH\Leave\LeaveType::find($leaveTypeId);
        if (!$leaveType || !$leaveType->service_years_based) {
            return $data;
        }

        $employee = \App\Models\RH\Employee\Employee::find($data['employee_id']);
        if (!$employee) {
            return $data;
        }

        $data['total_days_entitled'] = $this->entitlementService->entitledDays($employee, $leaveType);

        return $data;
    }
}
