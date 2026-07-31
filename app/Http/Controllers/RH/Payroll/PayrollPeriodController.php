<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Payroll\PayrollPeriodRequest;
use App\Services\RH\Payroll\PayrollPeriodService;

class PayrollPeriodController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Período de Folha de Pagamento';
    protected ?string $fieldName = 'name';

    public function __construct(PayrollPeriodService $service)
    {
        $this->service = $service;
    }

    public function store(PayrollPeriodRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(PayrollPeriodRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
