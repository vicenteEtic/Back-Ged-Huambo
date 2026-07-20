<?php

namespace App\Http\Controllers\RH\Department;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Department\DepartmentRequest;
use App\Services\RH\Department\DepartmentService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Department';
    protected ?string $fieldName = 'name';

    public function __construct(DepartmentService $service)
    {
        $this->service = $service;
    }

    public function store(DepartmentRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $department = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'rh',
                level: 'info',
                customMessage: 'Department ' . $department->name . ' created by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($department, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating department', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(DepartmentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $department = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh',
                level: 'info',
                customMessage: 'Department ' . $department->name . ' updated by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($department, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating department', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
