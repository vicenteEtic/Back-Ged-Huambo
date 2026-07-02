<?php

namespace App\Http\Controllers\RH\Leave;

use App\Models\RH\Leave\LeaveRequest;
use App\Services\RH\Leave\LeaveApprovalService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LeaveApprovalController
{
    public function __construct(
        protected LeaveApprovalService $approvalService,
    ) {}

    public function approve(Request $request, int $leaveRequestId)
    {
        try {
            $request->validate(['comment' => 'nullable|string']);
            $model = $this->approvalService->approve($leaveRequestId, auth()->id(), $request->comment);
            return response()->json($model);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error approving leave', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reject(Request $request, int $leaveRequestId)
    {
        try {
            $request->validate(['comment' => 'required|string']);
            $model = $this->approvalService->reject($leaveRequestId, auth()->id(), $request->comment);
            return response()->json($model);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error rejecting leave', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function pending()
    {
        try {
            $pending = LeaveRequest::with(['employee', 'leaveType'])
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get();
            return response()->json($pending);
        } catch (Exception $e) {
            Log::error('Error fetching pending leaves', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
