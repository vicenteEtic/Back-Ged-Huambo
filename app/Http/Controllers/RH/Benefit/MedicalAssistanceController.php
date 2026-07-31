<?php

namespace App\Http\Controllers\RH\Benefit;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Benefit\MedicalAssistanceRequest;
use App\Services\RH\Benefit\MedicalAssistanceService;

class MedicalAssistanceController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Assistência Médica';
    protected ?string $fieldName = 'id';

    public function __construct(MedicalAssistanceService $service)
    {
        $this->service = $service;
    }

    public function store(MedicalAssistanceRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(MedicalAssistanceRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
