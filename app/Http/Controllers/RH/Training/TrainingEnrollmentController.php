<?php

namespace App\Http\Controllers\RH\Training;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Training\TrainingEnrollmentRequest;
use App\Services\RH\Training\TrainingEnrollmentService;

class TrainingEnrollmentController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Inscrição de Formação';
    protected ?string $fieldName = 'id';

    public function __construct(TrainingEnrollmentService $service)
    {
        $this->service = $service;
    }

    public function store(TrainingEnrollmentRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(TrainingEnrollmentRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
