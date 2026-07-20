<?php

namespace App\Http\Controllers\RH\Department;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Department\DepartmentPermissionRequest;
use App\Services\RH\Department\DepartmentPermissionService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentPermissionController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'DepartmentPermission';
    protected ?string $fieldName = 'id';

    public function __construct(DepartmentPermissionService $service)
    {
        $this->service = $service;
    }

    public function store(DepartmentPermissionRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $data['granted_by'] = auth()->id();
            $permission = $this->service->store($data);
            DB::commit();
            return response()->json($permission->load(['permission', 'area', 'grantedBy']), Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating department permission', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function byDepartment(int $departmentId)
    {
        try {
            $permissions = $this->service->byDepartment($departmentId);
            return response()->json($permissions);
        } catch (Exception $e) {
            Log::error('Error fetching department permissions', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
