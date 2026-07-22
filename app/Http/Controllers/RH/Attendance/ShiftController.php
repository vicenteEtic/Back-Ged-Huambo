<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Attendance\ShiftRequest;
use App\Services\RH\Attendance\ShiftService;

class ShiftController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Turno';
    protected ?string $fieldName = 'name';

    public function __construct(ShiftService $service)
    {
        $this->service = $service;
    }

    public function store(ShiftRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(ShiftRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
