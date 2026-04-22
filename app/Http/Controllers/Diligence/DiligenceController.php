<?php

namespace App\Http\Controllers\Diligence;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\Diligence\DiligenceRequest;
use App\Services\Diligence\DiligenceService;
use Exception; 
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class DiligenceController extends AbstractController
{
    protected ?string $nameEntity = "Diligência";
    protected ?string $fieldName = "name";
    protected ?string $logType = 'user';

    public function __construct(DiligenceService $service)
    {
        $this->service = $service;
    }

    public function store(DiligenceRequest $request)
    {
        try {
            $this->logRequest();
            $diligence = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'diligence',
                level: 'info',
                customMessage: "O usuário " . auth()->user()->first_name . " criou a diligência {$diligence->name} com sucesso."
            );
            return response()->json($diligence, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
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
    public function update(DiligenceRequest $request, $id)
    {
        try {
            $this->logRequest();
            $diligence = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'diligence',
                level: 'info',
                customMessage: "O usuário " . auth()->user()->first_name . " atualizou a diligência {$diligence->name} com sucesso."
            );
            return response()->json($diligence, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
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
