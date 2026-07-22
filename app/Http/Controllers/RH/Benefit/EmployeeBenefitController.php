<?php

namespace App\Http\Controllers\RH\Benefit;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Benefit\EmployeeBenefitRequest;
use App\Services\RH\Benefit\EmployeeBenefitService;

class EmployeeBenefitController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Benefício do Funcionário';
    protected ?string $fieldName = 'id';

    public function __construct(EmployeeBenefitService $service)
    {
        $this->service = $service;
    }

    public function store(EmployeeBenefitRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(EmployeeBenefitRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
