<?php

namespace App\Http\Controllers\RH\Department;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Department\DepartmentRequest;
use App\Services\RH\Department\DepartmentService;

class DepartmentController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Departamento';
    protected ?string $fieldName = 'name';

    public function __construct(DepartmentService $service)
    {
        $this->service = $service;
    }

    public function store(DepartmentRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(DepartmentRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
