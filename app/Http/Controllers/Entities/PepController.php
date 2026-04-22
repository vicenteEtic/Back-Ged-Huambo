<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\AbstractController;
use App\Services\Entities\PepService;
use App\Http\Requests\Entities\PepRequest;
use Exception; 
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PepController extends AbstractController
{
    protected ?string $logType = 'user';
    protected ?string $nameEntity = "Pessoa Exposta Politicamente";
    protected ?string $fieldName = "name";

    public function __construct(PepService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PepRequest $request)
    {
        try {
            $this->logRequest();
            $pep = $this->service->store($request->validated());
            return response()->json($pep, Response::HTTP_CREATED);
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
    public function update(PepRequest $request, $id)
    {
        try {
            $this->logRequest();
            $pep = $this->service->update($request->validated(), $id);
            return response()->json($pep, Response::HTTP_OK);
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

    public function pepExternalGetAll(Request $request)
    {
        try {
            $this->logRequest();
    
            $peps = $this->service->GetPepExternal($request->all());
    
            return response()->json($peps, Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logRequest($e);
    
            return response()->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    

    public function getDataSanctionExternal(Request $request)
    {
        try {
            $this->logRequest();
    
            $peps = $this->service->getAllSanctions($request->all());
    
            return response()->json($peps, Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logRequest($e);
    
            return response()->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
