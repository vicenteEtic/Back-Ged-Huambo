<?php

namespace App\Http\Controllers\RH\Department;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Department\DepartmentPermissionRequest;
use App\Services\RH\Department\DepartmentPermissionService;

class DepartmentPermissionController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Permissão de Departamento';
    protected ?string $fieldName = 'id';

    public function __construct(DepartmentPermissionService $service)
    {
        $this->service = $service;
    }

    public function store(DepartmentPermissionRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $data = $request->validated();
            $data['granted_by'] = auth()->id();
            $permission = $this->service->store($data);
            return $permission->load(['permission', 'area', 'grantedBy']);
        });
    }

    public function byDepartment(int $departmentId)
    {
        try {
            $permissions = $this->service->byDepartment($departmentId);
            return response()->json($permissions);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao buscar permissões de departamento', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
