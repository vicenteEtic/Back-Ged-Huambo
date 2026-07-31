<?php

namespace App\Http\Controllers\RH\Disciplinary;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Disciplinary\DisciplinaryTypeRequest;
use App\Services\RH\Disciplinary\DisciplinaryTypeService;

class DisciplinaryTypeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Tipo Disciplinar';
    protected ?string $fieldName = 'name';

    public function __construct(DisciplinaryTypeService $service)
    {
        $this->service = $service;
    }

    public function store(DisciplinaryTypeRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(DisciplinaryTypeRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
