<?php

namespace App\Http\Controllers\RH\Recruitment;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Recruitment\JobOpeningRequest;
use App\Services\RH\Recruitment\JobOpeningService;

class JobOpeningController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Vaga';
    protected ?string $fieldName = 'title';

    public function __construct(JobOpeningService $service)
    {
        $this->service = $service;
    }

    public function store(JobOpeningRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(JobOpeningRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
