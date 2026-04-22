<?php

namespace App\Http\Controllers\Entities;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Services\Entities\EntitiesService;
use App\Http\Controllers\AbstractController;
use App\Http\Requests\Entities\EntitiesRequest;
use App\Http\Requests\Entities\ImportDataRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EntitiesController extends AbstractController
{
    protected ?string $logType = 'entity';
    protected ?string $nameEntity = "Entidade";
    protected ?string $fieldName = "social_denomination";

    public function __construct(EntitiesService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EntitiesRequest $request)
    {
        try {
            $this->logRequest();
            return $entities = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                customMessage: "O usuário " . Auth::user()->first_name . " criou a entidade {$entities->social_denomination} com sucesso.",
                idEntity: $entities->id
            );*


            return response()->json($entities, Response::HTTP_CREATED);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'entity',
                level: 'error',
                customMessage: "Erro ao criar entidade.",
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
    public function update(EntitiesRequest $request, $id)
    {
        try {
            $this->logRequest();
            $entities = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                customMessage: "O usuário " . Auth::user()->first_name . " atualizou a entidade {$entities->social_denomination} com sucesso.",
                idEntity: $entities->id
            );
            return response()->json($entities, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'entity',
                level: 'error',
                customMessage: "Erro ao atualizar entidade com ID {$id}.",
            );
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'entity',
                level: 'error',
                customMessage: "Erro ao atualizar entidade.",
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

    public function storeImportData(ImportDataRequest $request)
    {


        try {
            $this->logRequest();
            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                customMessage: "O usuário " . Auth::user()->first_name . " iniciou a importação de entidades.",
            );
            $batchId = $this->service->initializeImportBatch(Auth::id());
            $this->service->dispatchImportJobs($request->validated(), Auth::id(), $batchId);
            return response()->json("Importação em progresso", Response::HTTP_OK);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'entity',
                level: 'error',
                customMessage: "Erro ao iniciar importação de entidades."
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
    public function getProfileSegmentation()
    {
        try {
            $this->logRequest();
            $result = $this->service->profileSegmentation();
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'entity',
                level: 'error',
                customMessage: "Erro ao segmentar perfil das entidade",
            );
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'entity',
                level: 'error',
                customMessage: "Erro ao segmentar perfil da entidade.",
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
