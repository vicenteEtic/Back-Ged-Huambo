<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Payroll\IrtBracketRequest;
use App\Services\RH\Payroll\IrtBracketService;

class IrtBracketController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Escalão IRT';
    protected ?string $fieldName = 'bracket';

    public function __construct(IrtBracketService $service)
    {
        $this->service = $service;
    }

    public function store(IrtBracketRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(IrtBracketRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }
}
