<?php

namespace App\Services\RH\Career;

use App\Models\RH\Career\ProgressionApproval;
use App\Models\RH\Career\ProgressionRequest;
use App\Models\RH\Employee\Employee;
use App\Notifications\RH\ProgressionApprovedNotification;
use App\Notifications\RH\ProgressionRejectedNotification;
use App\Notifications\RH\ProgressionSubmittedNotification;
use App\Repositories\RH\Career\ProgressionRequestRepository;
use App\Services\AbstractService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ProgressionRequestService extends AbstractService
{
    public function __construct(
        ProgressionRequestRepository $repository,
        protected ProgressionRuleService $ruleService
    ) {
        parent::__construct($repository);
    }

    public function submit(array $data): ProgressionRequest
    {
        $employee = Employee::with('department.responsible')->findOrFail($data['employee_id']);
        $rule = null;

        if (!empty($data['rule_id'])) {
            $rule = \App\Models\RH\Career\ProgressionRule::findOrFail($data['rule_id']);
            $data['type'] ??= $rule->type;
            $data['to_category'] ??= $rule->to_category;
            $data['increase_percent'] = $rule->salary_increase_percent;
            $data['new_salary'] = $this->ruleService->calculateNewSalary($employee, $rule);
        }

        $data['current_salary'] ??= $employee->base_salary;
        $data['from_category'] ??= $employee->category;
        $data['from_position_id'] ??= $employee->position_id;
        $data['status'] = 'pending';
        $data['requested_by'] ??= auth()->id();

        $progression = $this->store($data);

        // Notify department head
        if ($employee->department?->responsible) {
            Notification::send($employee->department->responsible, new ProgressionSubmittedNotification($progression));
        }

        return $progression;
    }

    public function approve(int $id, int $approverId, ?string $comment = null): ProgressionRequest
    {
        return DB::transaction(function () use ($id, $approverId, $comment) {
            $request = ProgressionRequest::with('approvals', 'employee.user')->findOrFail($id);
            $this->createOrUpdateApproval($request, $approverId, 'approved', $comment);
            $this->updateRequestStatus($request);

            if ($request->employee?->user) {
                Notification::send($request->employee->user, new ProgressionApprovedNotification($request->fresh()));
            }

            return $request->fresh(['approvals']);
        });
    }

    public function reject(int $id, int $approverId, string $comment): ProgressionRequest
    {
        return DB::transaction(function () use ($id, $approverId, $comment) {
            $request = ProgressionRequest::with('employee.user')->findOrFail($id);
            $this->createOrUpdateApproval($request, $approverId, 'rejected', $comment);
            $request->update(['status' => 'rejected']);

            if ($request->employee?->user) {
                Notification::send($request->employee->user, new ProgressionRejectedNotification($request->fresh(), $comment));
            }

            return $request->fresh(['approvals']);
        });
    }

    public function execute(int $id): ProgressionRequest
    {
        return DB::transaction(function () use ($id) {
            $request = ProgressionRequest::findOrFail($id);
            $employee = $request->employee;

            $employee->update([
                'category' => $request->to_category ?? $employee->category,
                'position_id' => $request->to_position_id ?? $employee->position_id,
                'base_salary' => $request->new_salary,
            ]);

            $employee->functionalHistory()->create([
                'type' => $request->type === 'promotion' ? 'promotion' : 'progression',
                'previous_value' => ['category' => $request->from_category, 'position_id' => $request->from_position_id, 'salary' => $request->current_salary],
                'new_value' => ['category' => $request->to_category, 'position_id' => $request->to_position_id, 'salary' => $request->new_salary],
                'effective_date' => $request->effective_date ?? today(),
                'document_reference' => "PROG-{$request->id}",
                'created_by' => auth()->id(),
            ]);

            $request->update(['status' => 'executed']);
            return $request->fresh();
        });
    }

    private function createOrUpdateApproval(ProgressionRequest $request, int $approverId, string $status, ?string $comment): void
    {
        $maxLevel = $request->approvals->max('level') ?? 0;
        ProgressionApproval::updateOrCreate(
            ['progression_request_id' => $request->id, 'approver_id' => $approverId],
            ['level' => $maxLevel + 1, 'status' => $status, 'comment' => $comment, 'decided_at' => now()]
        );
    }

    private function updateRequestStatus(ProgressionRequest $request): void
    {
        $allApproved = $request->approvals->where('status', 'approved')->count() >= 1;
        $anyRejected = $request->approvals->where('status', 'rejected')->isNotEmpty();

        $newStatus = $anyRejected ? 'rejected' : ($allApproved ? 'approved' : 'pending');
        $request->update(['status' => $newStatus]);
    }
}
