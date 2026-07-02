<?php

namespace App\Http\Controllers\RH\Career;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Career\ProgressionRuleRequest;
use App\Models\RH\Employee\Employee;
use App\Services\RH\Career\ProgressionRuleService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgressionRuleController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'ProgressionRule';
    protected ?string $fieldName = 'name';

    public function __construct(
        ProgressionRuleService $service,
        protected ProgressionRuleService $ruleService
    ) {
        $this->service = $service;
    }

    public function store(ProgressionRuleRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->store($request->validated());
            DB::commit();
            return response()->json($model, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating progression rule', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(ProgressionRuleRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $model = $this->service->update($request->validated(), $id);
            DB::commit();
            return response()->json($model, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating progression rule', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkEligibility(int $employeeId, ?int $ruleId = null)
    {
        try {
            $employee = Employee::findOrFail($employeeId);
            $rule = $ruleId ? \App\Models\RH\Career\ProgressionRule::findOrFail($ruleId) : null;
            return response()->json($this->ruleService->checkEligibility($employee, $rule));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error checking eligibility', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
