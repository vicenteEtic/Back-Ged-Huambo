<?php

namespace App\Http\Controllers\RH\FunctionalHistory;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\FunctionalHistory\FunctionalHistoryRequest;
use App\Services\RH\FunctionalHistory\FunctionalHistoryService;

class FunctionalHistoryController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Histórico Funcional';
    protected ?string $fieldName = 'id';

    public function __construct(FunctionalHistoryService $service)
    {
        $this->service = $service;
    }

    public function store(FunctionalHistoryRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] ??= auth()->id();
            return $this->service->store($data);
        });
    }

    public function update(FunctionalHistoryRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
