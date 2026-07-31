<?php

namespace App\Http\Controllers\RH\Recruitment;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Recruitment\CandidateRequest;
use App\Services\RH\Recruitment\CandidateService;

class CandidateController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Candidato';
    protected ?string $fieldName = 'full_name';

    public function __construct(CandidateService $service)
    {
        $this->service = $service;
    }

    public function store(CandidateRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(CandidateRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
