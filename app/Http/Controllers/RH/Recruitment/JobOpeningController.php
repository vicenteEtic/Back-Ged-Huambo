<?php

namespace App\Http\Controllers\RH\Recruitment;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Recruitment\JobOpeningRequest;
use App\Services\RH\Recruitment\JobOpeningService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobOpeningController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'JobOpening';
    protected ?string $fieldName = 'title';

    public function __construct(JobOpeningService $service)
    {
        $this->service = $service;
    }

    public function store(JobOpeningRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $jobOpening = $this->service->store($request->validated());
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Vaga ' . $jobOpening->title . ' criada por ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($jobOpening, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao criar vaga', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(JobOpeningRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $jobOpening = $this->service->update($request->validated(), $id);
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Vaga ' . $jobOpening->title . ' atualizada por ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($jobOpening, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao atualizar vaga', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
