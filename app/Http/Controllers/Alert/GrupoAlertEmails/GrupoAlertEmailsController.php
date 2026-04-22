<?php
    
    namespace App\Http\Controllers\Alert\GrupoAlertEmails;
    
    use App\Http\Controllers\AbstractController;
    use App\Services\Alert\GrupoAlertEmails\GrupoAlertEmailsService;
    use App\Http\Requests\Alert\GrupoAlertEmails\GrupoAlertEmailsRequest;
    use Exception; 
use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class GrupoAlertEmailsController extends AbstractController
    {
        public function __construct(GrupoAlertEmailsService $service)
        {
            $this->service = $service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store(GrupoAlertEmailsRequest $request)
        {
            try {
                $this->logRequest();
                $grupoAlertEmails = $this->service->store($request->validated());
                return response()->json($grupoAlertEmails, Response::HTTP_CREATED);
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
        public function update(GrupoAlertEmailsRequest $request, $id)
        {
            try {
                $this->logRequest();
                $grupoAlertEmails = $this->service->update($request->validated(), $id);
                return response()->json($grupoAlertEmails, Response::HTTP_OK);
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