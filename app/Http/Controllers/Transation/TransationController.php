<?php

namespace App\Http\Controllers\Transation;

use App\Http\Controllers\AbstractController;
use App\Services\Transation\TransationService;
use App\Http\Requests\Transation\TransationRequest;
use App\Jobs\EvaluateTransationJob;
use Exception;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class TransationController extends AbstractController
{
    public function __construct(TransationService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(TransationRequest $request)
{
   try {
        $this->logRequest();

        $result = $this->service->storeManyTransactions($request->validated());

        return response()->json([
            'status' => 'ok',
            'transaction' => $result['transactions'],
            'ml_evaluation' => $result['ml_evaluation']
        ], Response::HTTP_CREATED);
    } catch (\Exception $e) {
        $this->logRequest($e);
        return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}



    /**
     * Update the specified resource in storage.
     */
    public function update(TransationRequest $request, $id)
    {
        try {
            $this->logRequest();
            $transation = $this->service->update($request->validated(), $id);
            return response()->json($transation, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
