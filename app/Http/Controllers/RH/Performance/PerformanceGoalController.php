<?php

namespace App\Http\Controllers\RH\Performance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Performance\PerformanceGoalRequest;
use App\Services\RH\Performance\PerformanceGoalService;

class PerformanceGoalController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Objectivo de Desempenho';
    protected ?string $fieldName = 'title';

    public function __construct(PerformanceGoalService $service)
    {
        $this->service = $service;
    }

    public function store(PerformanceGoalRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(PerformanceGoalRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
