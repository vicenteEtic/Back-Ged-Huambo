<?php

namespace App\Http\Controllers\Transation;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\Transation\Policies\PoliciesRequest;
use App\Services\Transation\TransactionService;
use App\Http\Requests\Transation\TransactionRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TransactionController extends AbstractController
{
    public function __construct(TransactionService $service)
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

        $records = $request->validated()['records'];
        $userId  = Auth::id();

        // 🚀 DISPARA DEPOIS DA RESPOSTA HTTP
        dispatch(function () use ($records, $userId) {
            app(\App\Services\Transation\TransactionService::class)
                ->dispatchImportJobs($records, $userId);
        })->afterResponse();

        // ⚡ resposta imediata
        return response()->json([
            'success'        => true,
            'message'        => 'Importação recebida e será processada em background',
            'total_records'  => count($records),
        ], Response::HTTP_ACCEPTED);

    } catch (\Throwable $e) {
        $this->logRequest($e);

        return response()->json([
            'success' => false,
            'message' => 'Erro ao iniciar importação',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionRequest $request, $id)
    {
        try {
            $this->logRequest();
            $transaction = $this->service->update($request->validated(), $id);
            return response()->json($transaction, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
