<?php

namespace App\Http\Controllers\Indicator;

use Exception; 
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\Log\LogService;
use App\Http\Controllers\AbstractController;
use App\Services\Indicator\IndicatorService;
use App\Http\Requests\Indicator\IndicatorRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class IndicatorController extends AbstractController
{
    public function __construct(IndicatorService $service, LogService $logService)
    {
        parent::__construct($service, $logService);
    }
   

    public function store(IndicatorRequest $request)
    {
        try {
            $this->logRequest();
            $indicator = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "O usuário " . auth()->user()->first_name . " criou o indicador {$indicator->name} com sucesso.",
            );
            return response()->json($indicator, Response::HTTP_CREATED);
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

     public function getIndicatorsByFk()
    {
        try {
            $this->logRequest();
            $indicator = $this->service->getIndicatorsByFk();
          
            return response()->json($indicator, Response::HTTP_CREATED);
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
    public function update(IndicatorRequest $request, $id)
    {
        try {
            $this->logRequest();
            $indicator = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "O usuário " . auth()->user()->first_name . " atualizou o indicador {$indicator->name} com sucesso.",
            );
            return response()->json($indicator, Response::HTTP_OK);
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
