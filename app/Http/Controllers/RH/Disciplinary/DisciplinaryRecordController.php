<?php

namespace App\Http\Controllers\RH\Disciplinary;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Disciplinary\DisciplinaryRecordRequest;
use App\Services\RH\Disciplinary\DisciplinaryRecordService;

class DisciplinaryRecordController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Registo Disciplinar';
    protected ?string $fieldName = 'id';

    public function __construct(DisciplinaryRecordService $service)
    {
        $this->service = $service;
    }

    public function store(DisciplinaryRecordRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(DisciplinaryRecordRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
