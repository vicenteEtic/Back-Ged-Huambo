<?php

namespace App\Http\Controllers\RH\Declaration;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Declaration\DeclarationTypeForm;
use App\Services\RH\Declaration\DeclarationTypeService;

class DeclarationTypeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Tipo de Declaração';
    protected ?string $fieldName = 'name';

    public function __construct(DeclarationTypeService $service)
    {
        $this->service = $service;
    }

    public function store(DeclarationTypeForm $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
            'Tipo de declaração criado por ' . auth()->user()->first_name
        );
    }

    public function update(DeclarationTypeForm $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
