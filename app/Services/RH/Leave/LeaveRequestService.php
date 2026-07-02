<?php

namespace App\Services\RH\Leave;

use App\Models\RH\Leave\LeaveRequest;
use App\Repositories\RH\Leave\LeaveRequestRepository;
use App\Services\AbstractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveRequestService extends AbstractService
{
    public function __construct(
        LeaveRequestRepository $repository,
        protected LeavePlanService $planService,
        protected LeaveApprovalService $approvalService,
    ) {
        parent::__construct($repository);
    }

    public function submit(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $data['total_days'] = $this->calculateBusinessDays($data['start_date'], $data['end_date']);
            $data['status'] = 'pending';

            if (empty($data['leave_plan_id'])) {
                $plan = \App\Models\RH\Leave\LeavePlan::firstOrCreate(
                    ['employee_id' => $data['employee_id'], 'year' => Carbon::parse($data['start_date'])->year],
                    ['total_days_entitled' => 22, 'created_by' => auth()->id()]
                );
                $data['leave_plan_id'] = $plan->id;
            }

            $leaveRequest = $this->store($data);
            $this->planService->syncBalance($data['leave_plan_id']);

            return $leaveRequest->fresh(['employee', 'leaveType', 'leavePlan', 'approvals']);
        });
    }

    public function calculateBusinessDays(string $start, string $end): int
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $days = 0;

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if ($d->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }

    public function balanceByEmployee(int $employeeId, int $year): array
    {
        $plan = \App\Models\RH\Leave\LeavePlan::firstOrCreate(
            ['employee_id' => $employeeId, 'year' => $year],
            ['total_days_entitled' => 22, 'created_by' => auth()->id()]
        );
        $this->planService->syncBalance($plan->id);
        $plan->refresh();

        return [
            'employee_id' => $employeeId,
            'year' => $year,
            'total_days_entitled' => $plan->total_days_entitled,
            'days_used' => $plan->days_used,
            'days_pending' => $plan->days_pending,
            'days_remaining' => $plan->days_remaining,
        ];
    }
}
