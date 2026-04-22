<?php
    
    namespace App\Http\Controllers\KYT;
    
    use App\Http\Controllers\AbstractController;
    use App\Services\KYT\kytrulesService;
    use App\Http\Requests\KYT\kytrulesRequest;
    use Exception; 
use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class kytrulesController extends AbstractController
    {
        public function __construct(kytrulesService $service)
        {
            $this->service = $service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store(kytrulesRequest $request)
        {
            try {
                $this->logRequest();
                $kytrules = $this->service->store($request->validated());
                return response()->json($kytrules, Response::HTTP_CREATED);
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
        public function update(kytrulesRequest $request, $id)
        {
            try {
                $this->logRequest();
                $kytrules = $this->service->update($request->validated(), $id);
                return response()->json($kytrules, Response::HTTP_OK);
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