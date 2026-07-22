<?php

namespace App\Http\Controllers\RH\Employee;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Employee\EmployeeRequest;
use App\Services\RH\Employee\EmployeeService;

class EmployeeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Funcionário';
    protected ?string $fieldName = 'full_name';

    public function __construct(EmployeeService $service)
    {
        $this->service = $service;
    }

    public function store(EmployeeRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(EmployeeRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
