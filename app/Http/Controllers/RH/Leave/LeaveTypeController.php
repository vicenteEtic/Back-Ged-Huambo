<?php

namespace App\Http\Controllers\RH\Leave;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Leave\LeaveTypeRequest;
use App\Services\RH\Leave\LeaveTypeService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveTypeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'LeaveType';
    protected ?string $fieldName = 'name';

    public function __construct(LeaveTypeService $service)
    {
        $this->service = $service;
    }

    public function store(LeaveTypeRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $leaveType = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Leave type ' . $leaveType->name . ' created by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($leaveType, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating leave type', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(LeaveTypeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $leaveType = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Leave type ' . $leaveType->name . ' updated by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($leaveType, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating leave type', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
