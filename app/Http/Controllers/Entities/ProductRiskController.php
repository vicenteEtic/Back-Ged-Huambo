<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\AbstractController;
use App\Services\Entities\ProductRiskService;
use App\Http\Requests\Entities\ProductRiskRequest;
use Exception; 
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class ProductRiskController extends AbstractController
{
    protected ?string $logType = 'entity';
    protected ?string $nameEntity = "Produto de Risco";
    protected ?string $fieldName = "product->description";

    public function __construct(ProductRiskService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRiskRequest $request)
    {
        try {
            $this->logRequest();
            $productRisk = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "O usuário " . auth()->user()->first_name . " criou o produto de risco {$productRisk->product?->description} com sucesso.",
                idEntity: $productRisk->id
            );
            return response()->json($productRisk, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: "Erro ao criar produto de risco.",
            );
             Log::error('Erro interno', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    return response()->json([
        'error' => 'Erro interno no servidor.'
    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRiskRequest $request, $id)
    {
        try {
            $this->logRequest();
            $productRisk = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "O usuário " . auth()->user()->first_name . " atualizou o produto de risco {$productRisk->product?->description} com sucesso.",
                idEntity: $productRisk->id
            );
            return response()->json($productRisk, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: "O usuário " . auth()->user()->first_name . " tentou atualizar um produto de risco que não existe."
            );
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'user',
                level: 'error',
                customMessage: "O usuário " . auth()->user()->first_name . " tentou atualizar o produto de risco {$id}, mas ocorreu um erro."
            );
             Log::error('Erro interno', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    return response()->json([
        'error' => 'Erro interno no servidor.'
    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
