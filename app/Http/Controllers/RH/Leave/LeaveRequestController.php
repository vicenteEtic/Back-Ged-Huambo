<?php

namespace App\Http\Controllers\RH\Leave;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Leave\LeaveRequestForm;
use App\Services\RH\Leave\LeaveRequestService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveRequestController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'LeaveRequest';
    protected ?string $fieldName = 'id';

    public function __construct(
        LeaveRequestService $service,
        protected LeaveRequestService $leaveService
    ) {
        $this->service = $service;
    }

    public function store(LeaveRequestForm $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $leaveRequest = $this->leaveService->submit($request->validated());
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Leave request #' . $leaveRequest->id . ' submitted by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($leaveRequest, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error submitting leave request', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(LeaveRequestForm $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $leaveRequest = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Leave request #' . $leaveRequest->id . ' updated by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($leaveRequest, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating leave request', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function balance(int $employeeId)
    {
        try {
            $year = request('year', now()->year);
            return response()->json($this->leaveService->balanceByEmployee($employeeId, $year));
        } catch (Exception $e) {
            Log::error('Error fetching leave balance', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
