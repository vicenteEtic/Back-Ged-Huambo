<?php

namespace App\Http\Controllers\KYT;

use App\Http\Controllers\AbstractController;
use App\Services\KYT\KytRuleService;
use App\Http\Requests\KYT\KytRuleRequest;

class KytRuleController extends AbstractController
{
    public function __construct(KytRuleService $service)
    {
        $this->service = $service;
        $this->nameEntity = 'KYT Rule';
        $this->fieldName = 'name';
        $this->logType = 'kyt_rules';
    }

    public function store(KytRuleRequest $request)
    {
        try {
            $this->logRequest();
            $rule = $this->service->store($request->validated());
            return response()->json($rule, \Illuminate\Http\Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logRequest($e);
            \Illuminate\Support\Facades\Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Erro interno no servidor.'], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(KytRuleRequest $request, $id)
    {
        try {
            $this->logRequest();
            $rule = $this->service->update($request->validated(), $id);
            return response()->json($rule, \Illuminate\Http\Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], \Illuminate\Http\Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logRequest($e);
            \Illuminate\Support\Facades\Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Erro interno no servidor.'], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
