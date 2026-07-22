<?php

namespace App\Http\Controllers\RH\Training;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Training\TrainingSessionRequest;
use App\Services\RH\Training\TrainingSessionService;

class TrainingSessionController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Sessão de Formação';
    protected ?string $fieldName = 'name';

    public function __construct(TrainingSessionService $service)
    {
        $this->service = $service;
    }

    public function store(TrainingSessionRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(TrainingSessionRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
