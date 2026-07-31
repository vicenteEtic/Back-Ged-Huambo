<?php

namespace App\Http\Controllers\RH\Benefit;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Benefit\BenefitTypeRequest;
use App\Services\RH\Benefit\BenefitTypeService;

class BenefitTypeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Tipo de Benefício';
    protected ?string $fieldName = 'name';

    public function __construct(BenefitTypeService $service)
    {
        $this->service = $service;
    }

    public function store(BenefitTypeRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(BenefitTypeRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
