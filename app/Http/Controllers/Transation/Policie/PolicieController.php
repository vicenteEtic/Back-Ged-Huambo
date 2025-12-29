<?php
    
    namespace App\Http\Controllers\Transation\Policie;
    
    use App\Http\Controllers\AbstractController;
    use App\Services\Transation\Policie\PolicieService;
    use App\Http\Requests\Transation\Policie\PolicieRequest;
    use Exception;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class PolicieController extends AbstractController
    {
        public function __construct(PolicieService $service)
        {
            $this->service = $service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store(PolicieRequest $request)
        {
            try {
                $this->logRequest();
                $policie = $this->service->store($request->validated());
                return response()->json($policie, Response::HTTP_CREATED);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        /**
         * Update the specified resource in storage.
         */
        public function update(PolicieRequest $request, $id)
        {
            try {
                $this->logRequest();
                $policie = $this->service->update($request->validated(), $id);
                return response()->json($policie, Response::HTTP_OK);
            } catch (ModelNotFoundException $e) {
                $this->logRequest($e);
                return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }