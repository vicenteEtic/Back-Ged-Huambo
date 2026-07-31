<?php

namespace App\Http\Controllers\RH\Leave;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Leave\LeaveTypeRequest;
use App\Services\RH\Leave\LeaveTypeService;

class LeaveTypeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Tipo de Licença';
    protected ?string $fieldName = 'name';

    public function __construct(LeaveTypeService $service)
    {
        $this->service = $service;
    }

    public function store(LeaveTypeRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(LeaveTypeRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
