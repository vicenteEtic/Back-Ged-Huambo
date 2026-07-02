<?php

namespace App\Services\RH\Leave;

use App\Models\RH\Leave\LeaveApproval;
use App\Models\RH\Leave\LeaveRequest;
use App\Repositories\RH\Leave\LeaveApprovalRepository;
use App\Services\AbstractService;
use Illuminate\Support\Facades\DB;

class LeaveApprovalService extends AbstractService
{
    public function __construct(
        LeaveApprovalRepository $repository,
        protected LeavePlanService $planService,
    ) {
        parent::__construct($repository);
    }

    public function approve(int $leaveRequestId, int $approverId, ?string $comment = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequestId, $approverId, $comment) {
            $request = LeaveRequest::with('approvals')->findOrFail($leaveRequestId);
            $this->createOrUpdateApproval($request->id, $approverId, 'approved', $comment);
            $this->updateRequestStatus($request);
            return $request->fresh(['approvals', 'leavePlan']);
        });
    }

    public function reject(int $leaveRequestId, int $approverId, string $comment): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequestId, $approverId, $comment) {
            $request = LeaveRequest::findOrFail($leaveRequestId);
            $this->createOrUpdateApproval($request->id, $approverId, 'rejected', $comment);
            $request->update(['status' => 'rejected']);
            if ($request->leave_plan_id) {
                $this->planService->syncBalance($request->leave_plan_id);
            }
            return $request->fresh(['approvals', 'leavePlan']);
        });
    }

    private function createOrUpdateApproval(int $requestId, int $approverId, string $status, ?string $comment): void
    {
        $maxLevel = LeaveApproval::where('leave_request_id', $requestId)->max('level') ?? 0;
        LeaveApproval::updateOrCreate(
            ['leave_request_id' => $requestId, 'approver_id' => $approverId],
            ['level' => $maxLevel + 1, 'status' => $status, 'comment' => $comment, 'decided_at' => now()]
        );
    }

    private function updateRequestStatus(LeaveRequest $request): void
    {
        $hasApproval = $request->approvals()->where('status', 'approved')->exists();
        $hasRejection = $request->approvals()->where('status', 'rejected')->exists();

        $newStatus = $hasRejection ? 'rejected' : ($hasApproval ? 'approved' : 'pending');
        $request->update([
            'status' => $newStatus,
            'approved_by' => $hasApproval ? $request->approvals()->where('status', 'approved')->first()?->approver_id : null,
            'approved_at' => $hasApproval ? now() : null,
        ]);

        if ($request->leave_plan_id && $newStatus === 'approved') {
            $this->planService->syncBalance($request->leave_plan_id);
        }
    }
}
