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
    protected ?string $nameEntity = 'Processo de Aposentação';
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
            Log::error('Erro ao verificar elegibilidade de aposentação', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function store(RetirementProcessRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(RetirementProcessRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function history(int $employeeId)
    {
        try {
            return response()->json($this->retirementService->processHistory($employeeId));
        } catch (Exception $e) {
            Log::error('Erro ao buscar histórico de aposentação', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
