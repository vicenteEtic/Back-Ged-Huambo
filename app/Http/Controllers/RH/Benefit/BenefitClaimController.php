<?php

namespace App\Http\Controllers\RH\Benefit;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Benefit\BenefitClaimRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BenefitClaimController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'BenefitClaim';
    protected ?string $fieldName = 'id';

    public function __construct(\App\Services\RH\Benefit\BenefitClaimService $service)
    {
        $this->service = $service;
    }

    public function store(BenefitClaimRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $data['requested_date'] ??= now()->toDateString();
            $model = $this->service->store($data);
            DB::commit();
            return response()->json($model->load('benefitType'), Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating benefit claim', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(BenefitClaimRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->update($request->validated(), $id);
            DB::commit();
            return response()->json($model->load('benefitType'), Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating benefit claim', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
