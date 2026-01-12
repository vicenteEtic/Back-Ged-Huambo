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
use Illuminate\Support\Facades\Storage;
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

        // ✅ Pega o JSON bruto da requisição
        $content = $request->getContent();

        // ✅ Salva em arquivo único no storage
        $path = Storage::put('imports/import_' . uniqid() . '.json', $content);

        $userId = Auth::id();

        // 🚀 Dispara o job de processamento depois da resposta HTTP
        ProcessImportJsonJob::dispatch($path, $userId)
            ->afterResponse(); // ⚡ garante retorno imediato

        // ⚡ resposta imediata para o front
        return response()->json([
            'success'       => true,
            'message'       => 'Importação recebida e será processada em background',
            'file_path'     => $path,
        ], Response::HTTP_ACCEPTED);

    } catch (\Throwable $e) {
        $this->logRequest($e);

        return response()->json([
            'success' => false,
            'message' => 'Erro ao iniciar importação',
            'error'   => $e->getMessage(),
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
