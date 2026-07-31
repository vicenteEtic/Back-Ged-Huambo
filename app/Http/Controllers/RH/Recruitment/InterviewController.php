<?php

namespace App\Http\Controllers\RH\Recruitment;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Recruitment\InterviewRequest;
use App\Services\RH\Recruitment\InterviewService;

class InterviewController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Entrevista';
    protected ?string $fieldName = 'id';

    public function __construct(InterviewService $service)
    {
        $this->service = $service;
    }

    public function store(InterviewRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(InterviewRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
