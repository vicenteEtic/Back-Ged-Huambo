<?php

namespace App\Http\Controllers\RH\Recruitment;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Recruitment\ApplicationRequest;
use App\Services\RH\Recruitment\ApplicationService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplicationController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Application';
    protected ?string $fieldName = 'id';

    public function __construct(ApplicationService $service)
    {
        $this->service = $service;
    }

    public function store(ApplicationRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $application = $this->service->store($request->validated());
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Candidatura #' . $application->id . ' criada por ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($application, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao criar candidatura', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(ApplicationRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $application = $this->service->update($request->validated(), $id);
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Candidatura #' . $application->id . ' atualizada por ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($application, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Erro ao atualizar candidatura', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
