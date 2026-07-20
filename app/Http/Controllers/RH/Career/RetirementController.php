<?php

namespace App\Http\Controllers\RH\Career;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Career\RetirementProcessRequest;
use App\Models\RH\Employee\Employee;
use App\Services\RH\Career\RetirementService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetirementController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'RetirementProcess';
    protected ?string $fieldName = 'id';

    public function __construct(
        \App\Repositories\RH\Career\RetirementProcessRepository $repository,
        protected RetirementService $retirementService,
    ) {
        $this->service = $repository;
    }

    public function eligibility(int $employeeId)
    {
        try {
            $employee = Employee::findOrFail($employeeId);
            return response()->json($this->retirementService->checkEligibility($employee));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error checking retirement eligibility', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(RetirementProcessRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->store($request->validated());
            $this->logToDatabase(type: 'rh', level: 'info', customMessage: 'Retirement process created by ' . auth()->user()->first_name);
            DB::commit();
            return response()->json($model->load(['employee', 'approver']), Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating retirement process', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(RetirementProcessRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->update($request->validated(), $id);
            DB::commit();
            return response()->json($model->load(['employee', 'approver']), Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating retirement process', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function history(int $employeeId)
    {
        try {
            return response()->json($this->retirementService->processHistory($employeeId));
        } catch (Exception $e) {
            Log::error('Error fetching retirement history', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
