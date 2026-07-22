<?php

namespace App\Http\Controllers\RH\Performance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Performance\PerformanceCycleRequest;
use App\Services\RH\Performance\PerformanceCycleService;

class PerformanceCycleController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Ciclo de Desempenho';
    protected ?string $fieldName = 'name';

    public function __construct(PerformanceCycleService $service)
    {
        $this->service = $service;
    }

    public function store(PerformanceCycleRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(PerformanceCycleRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
