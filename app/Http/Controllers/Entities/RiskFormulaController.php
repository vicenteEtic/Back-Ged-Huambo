<?php
    
    namespace App\Http\Controllers\Entities;
    
    use App\Http\Controllers\AbstractController;
    use App\Services\Entities\RiskFormulaService;
    use App\Http\Requests\Entities\RiskFormulaRequest;
    use Exception;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Response;
    
    class RiskFormulaController extends AbstractController
    {
        public function __construct(RiskFormulaService $service)
        {
            $this->service = $service;
        }
    
        /**
         * Store a newly created resource in storage.
         */
        public function store(RiskFormulaRequest $request)
        {
            try {
                $this->logRequest();
                $riskFormula = $this->service->store($request->validated());
                return response()->json($riskFormula, Response::HTTP_CREATED);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        /**
         * Update the specified resource in storage.
         */
        public function update(RiskFormulaRequest $request, $id)
        {
            try {
                $this->logRequest();
                $riskFormula = $this->service->update($request->validated(), $id);
                return response()->json($riskFormula, Response::HTTP_OK);
            } catch (ModelNotFoundException $e) {
                $this->logRequest($e);
                return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
            } catch (Exception $e) {
                $this->logRequest($e);
                return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }