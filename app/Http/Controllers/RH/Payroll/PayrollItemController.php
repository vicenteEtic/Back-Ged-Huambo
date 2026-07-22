<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Payroll\PayrollItemRequest;
use App\Helpers\PayrollCalculator;
use App\Services\RH\Payroll\PayrollItemService;

class PayrollItemController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Item de Folha de Pagamento';
    protected ?string $fieldName = 'id';

    public function __construct(PayrollItemService $service)
    {
        $this->service = $service;
    }

    public function store(PayrollItemRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $data = PayrollCalculator::calculate($request->validated());
            return $this->service->store($data);
        });
    }

    public function update(PayrollItemRequest $request, $id)
    {
        return $this->handleUpdate(function () use ($request, $id) {
            $data = PayrollCalculator::calculate($request->validated());
            return $this->service->update($data, $id);
        }, $id);
    }
}
