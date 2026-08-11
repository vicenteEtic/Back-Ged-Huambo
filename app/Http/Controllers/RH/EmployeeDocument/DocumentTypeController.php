<?php

namespace App\Http\Controllers\RH\EmployeeDocument;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\EmployeeDocument\DocumentTypeRequest;
use App\Services\RH\EmployeeDocument\DocumentTypeService;

class DocumentTypeController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Tipo de Documento';
    protected ?string $fieldName = 'name';

    public function __construct(DocumentTypeService $service)
    {
        parent::__construct($service);
    }

    public function store(DocumentTypeRequest $request)
    {
        return $this->handleStore(
            fn () => $this->service->store($request->validated()),
        );
    }

    public function update(DocumentTypeRequest $request, $id)
    {
        return $this->handleUpdate(
            fn () => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
