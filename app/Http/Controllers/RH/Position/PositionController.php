<?php

namespace App\Http\Controllers\RH\Position;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Position\PositionRequest;
use App\Services\RH\Position\PositionService;

class PositionController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Cargo';
    protected ?string $fieldName = 'name';

    public function __construct(PositionService $service)
    {
        $this->service = $service;
    }

    public function store(PositionRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(PositionRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
