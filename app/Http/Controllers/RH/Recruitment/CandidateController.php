<?php

namespace App\Http\Controllers\RH\Recruitment;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Recruitment\CandidateRequest;
use App\Services\RH\Recruitment\CandidateService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CandidateController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Candidate';
    protected ?string $fieldName = 'full_name';

    public function __construct(CandidateService $service)
    {
        $this->service = $service;
    }

    public function store(CandidateRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $candidate = $this->service->store($request->validated());
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Candidate ' . $candidate->full_name . ' created by ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($candidate, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating candidate', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(CandidateRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $candidate = $this->service->update($request->validated(), $id);
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Candidate ' . $candidate->full_name . ' updated by ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($candidate, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating candidate', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
