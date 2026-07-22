<?php

namespace App\Http\Controllers\RH\Recruitment;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Recruitment\ApplicationRequest;
use App\Services\RH\Recruitment\ApplicationService;

class ApplicationController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Candidatura';
    protected ?string $fieldName = 'id';

    public function __construct(ApplicationService $service)
    {
        $this->service = $service;
    }

    public function store(ApplicationRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(ApplicationRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
