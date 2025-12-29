<?php
    
    namespace App\Http\Controllers\Transation\CustomerProfiles;
    
    use App\Http\Controllers\AbstractController;
    use App\Services\Transation\CustomerProfiles\CustomerProfilesService;
    use App\Http\Requests\Transation\CustomerProfiles\CustomerProfilesRequest;
    use Exception;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class CustomerProfilesController extends AbstractController
    {
        public function __construct(CustomerProfilesService $service)
        {
            $this->service = $service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store(CustomerProfilesRequest $request)
        {
            try {
                $this->logRequest();
                $customerProfiles = $this->service->store($request->validated());
                return response()->json($customerProfiles, Response::HTTP_CREATED);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        /**
         * Update the specified resource in storage.
         */
        public function update(CustomerProfilesRequest $request, $id)
        {
            try {
                $this->logRequest();
                $customerProfiles = $this->service->update($request->validated(), $id);
                return response()->json($customerProfiles, Response::HTTP_OK);
            } catch (ModelNotFoundException $e) {
                $this->logRequest($e);
                return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }