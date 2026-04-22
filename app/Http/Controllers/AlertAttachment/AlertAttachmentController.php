<?php

namespace App\Http\Controllers\AlertAttachment;

use App\Http\Controllers\AbstractController;
use App\Services\AlertAttachment\AlertAttachmentService;
use App\Http\Requests\AlertAttachment\AlertAttachmentRequest;
use Exception; 
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class AlertAttachmentController extends AbstractController
{
    public function __construct(AlertAttachmentService $service)
    {
        $this->service = $service;
    }                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      

    /**
     * Store a newly created resource in storage.
     */
    public function store(AlertAttachmentRequest $request, $alertID)
    {
        try {
            $this->logRequest();
            // Pega arquivos enviados via form-data
            $files = $request->file('attachments');
    
            $alertAttachment = $this->service->createComplaintAttachment($files, $alertID);
            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                alert_id: $alertID,
           customMessage: "Adicionou um anexo ao alerta #{$alertID} '"


            );
            return response()->json($alertAttachment, Response::HTTP_CREATED);
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
    public function update(AlertAttachmentRequest $request, $alertID)
    {
        try {
            $this->logRequest();
            $alertAttachment = $this->service->update($request->validated(), $id);
            return response()->json($alertAttachment, Response::HTTP_OK);
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

    public function files($alertID)
    {
        try {
            $this->logRequest();
            $alertAttachment = $this->service->files($alertID);
            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                alert_id: $alertID,
           customMessage: "Listou os documentos do alerta #{$alertID} '"


            );
            return response()->json($alertAttachment, Response::HTTP_CREATED);
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

    public function showFile($id)
    {
        try {
            $filePath = $this->service->showFile($id); // Retorna caminho absoluto
    
            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Arquivo não encontrado.'], 404);
            }
    
            $mimeType = \Illuminate\Support\Facades\File::mimeType($filePath);
            $fileName = basename($filePath);
    
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => "Falha ao abrir o arquivo.",
                "error" => $th->getMessage()
            ], 400);
        }
    }
    
}
