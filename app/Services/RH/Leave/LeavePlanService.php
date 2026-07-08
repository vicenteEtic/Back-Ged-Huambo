<?php

namespace App\Services\RH\Leave;

use App\Models\RH\Leave\LeavePlan;
use App\Repositories\RH\Leave\LeavePlanRepository;
use App\Services\AbstractService;

class LeavePlanService extends AbstractService
{
    public function __construct(LeavePlanRepository $repository)
    {
        parent::__construct($repository);
    }

    public function store(array $data): \App\Models\RH\Leave\LeavePlan
    {
        $data = $this->clean($data);
        return LeavePlan::updateOrCreate(
            ['employee_id' => $data['employee_id'], 'year' => $data['year']],
            $data
        )->fresh();
    }

    public function syncBalance(int $planId): LeavePlan
    {
        $plan = LeavePlan::with('leaveRequests')->findOrFail($planId);
        $plan->days_used = round(
            $plan->leaveRequests->where('status', 'approved')->sum('total_days'), 1
        );
        $plan->days_pending = round(
            $plan->leaveRequests->where('status', 'pending')->sum('total_days'), 1
        );
        $plan->save();
        return $plan->fresh();
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
}
