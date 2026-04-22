<?php

namespace App\Http\Controllers\Alert\CommentAlert;

use App\Http\Controllers\AbstractController;
use App\Services\Alert\CommentAlert\CommentAlertService;
use App\Http\Requests\Alert\CommentAlert\CommentAlertRequest;
use Exception; 
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class CommentAlertController extends AbstractController
{
    public function __construct(CommentAlertService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CommentAlertRequest $request)
    {
        try {
            $this->logRequest();

            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                alert_id: (int) $request->validated()['alert_id'],
                customMessage: "Cadastrou um comentário ao alerta #{$request->validated()['alert_id']}.",

            );
            $commentAlert = $this->service->store($request->validated());
            return response()->json($commentAlert, Response::HTTP_CREATED);
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
    public function update(CommentAlertRequest $request, $id)
    {
        try {
            $this->logRequest();
            $commentAlert = $this->service->update($request->validated(), $id);

            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                alert_id: (int) $request->validated()['alert_id'],
                customMessage: "Atualizou o comentário do alerta #{$request->validated()['alert_id']}.",

            );

            return response()->json($commentAlert, Response::HTTP_OK);
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
