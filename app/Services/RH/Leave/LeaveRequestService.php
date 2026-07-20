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
    ) {
        parent::__construct($repository);
    }

    public function submit(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {
            $data['total_days'] = $this->calculateBusinessDays($data['start_date'], $data['end_date']);
            $data['status'] = 'pending';

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
                throw new \DomainException(
                    "Saldo insuficiente de {$typeName} para {$year}. Disponível: {$remaining} dia(s), solicitado: {$data['total_days']} dia(s)."
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

        if (!empty($notifiables)) {
            Notification::send($notifiables, new LeaveRequestSubmittedNotification($leaveRequest));
        }
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

    public function balanceByEmployee(int $employeeId, int $year, ?int $leaveTypeId = null): array
    {
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
            'balances' => $result,
        ];
    }
}
