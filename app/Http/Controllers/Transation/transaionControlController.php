<?php
    
    namespace App\Http\Controllers\Transation;
    
    use App\Http\Controllers\AbstractController;
    use App\Services\Transation\transaionControlService;
    use App\Http\Requests\Transation\transaionControlRequest;
    use Exception;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class transaionControlController extends AbstractController
    {
        public function __construct(transaionControlService $service)
        {
            $this->service = $service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store(transaionControlRequest $request)
        {
            try {
                $this->logRequest();
                $transaionControl = $this->service->store($request->validated());
                return response()->json($transaionControl, Response::HTTP_CREATED);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        /**
         * Update the specified resource in storage.
         */
        public function update(transaionControlRequest $request, $id)
        {
            try {
                $this->logRequest();
                $transaionControl = $this->service->update($request->validated(), $id);
                return response()->json($transaionControl, Response::HTTP_OK);
            } catch (ModelNotFoundException $e) {
                $this->logRequest($e);
                return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }