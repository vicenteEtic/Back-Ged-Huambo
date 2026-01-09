<?php
    
    namespace App\Http\Controllers\Transation;
    
    use App\Http\Controllers\AbstractController;
    use App\Services\Transation\PoliciesService;
    use App\Http\Requests\Transation\PoliciesRequest;
    use Exception;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class PoliciesController extends AbstractController
    {
        public function __construct(PoliciesService $service)
        {
            $this->service = $service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store(PoliciesRequest $request)
        {
            try {
                $this->logRequest();
                $policies = $this->service->store($request->validated());
                return response()->json($policies, Response::HTTP_CREATED);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        /**
         * Update the specified resource in storage.
         */
        public function update(PoliciesRequest $request, $id)
        {
            try {
                $this->logRequest();
                $policies = $this->service->update($request->validated(), $id);
                return response()->json($policies, Response::HTTP_OK);
            } catch (ModelNotFoundException $e) {
                $this->logRequest($e);
                return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }