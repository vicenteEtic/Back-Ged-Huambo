<?php
    
    namespace App\Http\Controllers\AmlAlert;
    
    use App\Http\Controllers\AbstractController;
    use App\Services\AmlAlert\AmlAlertService;
    use App\Http\Requests\AmlAlert\AmlAlertRequest;
    use Exception;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class AmlAlertController extends AbstractController
    {
        public function __construct(AmlAlertService $service)
        {
            $this->service = $service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store(AmlAlertRequest $request)
        {
            try {
                $this->logRequest();
                $amlAlert = $this->service->store($request->validated());
                return response()->json($amlAlert, Response::HTTP_CREATED);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        /**
         * Update the specified resource in storage.
         */
        public function update(AmlAlertRequest $request, $id)
        {
            try {
                $this->logRequest();
                $amlAlert = $this->service->update($request->validated(), $id);
                return response()->json($amlAlert, Response::HTTP_OK);
            } catch (ModelNotFoundException $e) {
                $this->logRequest($e);
                return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }