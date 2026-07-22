<?php

namespace App\Http\Controllers\RH\Benefit;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Benefit\BenefitClaimRequest;
use App\Services\RH\Benefit\BenefitClaimService;

class BenefitClaimController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Pedido de Benefício';
    protected ?string $fieldName = 'id';

    public function __construct(BenefitClaimService $service)
    {
        $this->service = $service;
    }

    public function store(BenefitClaimRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $data = $request->validated();
            $data['requested_date'] ??= now()->toDateString();
            $model = $this->service->store($data);
            return $model->load('benefitType');
        });
    }

    public function update(BenefitClaimRequest $request, $id)
    {
        return $this->handleUpdate(function () use ($request, $id) {
            $model = $this->service->update($request->validated(), $id);
            return $model->load('benefitType');
        }, $id);
    }
}
