<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Attendance\AbsenceTypeRequest;
use App\Services\RH\Attendance\AbsenceTypeService;

class AbsenceTypeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Tipo de Falta';
    protected ?string $fieldName = 'name';

    public function __construct(AbsenceTypeService $service)
    {
        $this->service = $service;
    }

    public function store(AbsenceTypeRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(AbsenceTypeRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
