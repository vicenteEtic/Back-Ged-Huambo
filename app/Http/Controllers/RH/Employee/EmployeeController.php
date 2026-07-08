<?php

namespace App\Http\Controllers\RH\Employee;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Employee\EmployeeRequest;
use App\Services\RH\Employee\EmployeeService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Employee';
    protected ?string $fieldName = 'full_name';

    public function __construct(EmployeeService $service)
    {
        $this->service = $service;
    }

    public function store(EmployeeRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();

            $data = $request->validated();
          
            $employee = $this->service->store($data);
            $this->logToDatabase(
                type: 'rh',
                level: 'info',
                customMessage: 'Employee ' . $employee->full_name . ' created by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($employee, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating employee', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(EmployeeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $employee = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh',
                level: 'info',
                customMessage: 'Employee ' . $employee->full_name . ' updated by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($employee, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating employee', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
