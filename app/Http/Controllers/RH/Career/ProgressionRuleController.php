<?php

namespace App\Http\Controllers\RH\Career;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Career\ProgressionRuleRequest;
use App\Models\RH\Employee\Employee;
use App\Services\RH\Career\ProgressionRuleService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ProgressionRuleController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Regra de Progressão';
    protected ?string $fieldName = 'name';

    public function __construct(
        ProgressionRuleService $service,
        protected ProgressionRuleService $ruleService
    ) {
        $this->service = $service;
    }

    public function store(ProgressionRuleRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(ProgressionRuleRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function checkEligibility(int $id, int $employeeId)
    {
        try {
            $employee = Employee::findOrFail($employeeId);
            $rule = \App\Models\RH\Career\ProgressionRule::findOrFail($id);
            return response()->json($this->ruleService->checkEligibility($employee, $rule));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao verificar elegibilidade', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
