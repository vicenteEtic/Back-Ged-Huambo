<?php

namespace App\Http\Controllers\RH\Training;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Training\TrainingCourseRequest;
use App\Services\RH\Training\TrainingCourseService;

class TrainingCourseController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Curso de Formação';
    protected ?string $fieldName = 'name';

    public function __construct(TrainingCourseService $service)
    {
        $this->service = $service;
    }

    public function store(TrainingCourseRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(TrainingCourseRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
