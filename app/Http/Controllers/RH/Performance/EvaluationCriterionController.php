<?php

namespace App\Http\Controllers\RH\Performance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Performance\EvaluationCriterionRequest;
use App\Services\RH\Performance\EvaluationCriterionService;

class EvaluationCriterionController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Critério de Avaliação';
    protected ?string $fieldName = 'name';

    public function __construct(EvaluationCriterionService $service)
    {
        $this->service = $service;
    }

    public function store(EvaluationCriterionRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(EvaluationCriterionRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
