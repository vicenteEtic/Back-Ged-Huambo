<?php

namespace App\Http\Controllers\RH\Leave;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Leave\LeavePlanRequest;
use App\Services\RH\Leave\LeavePlanService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeavePlanController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'LeavePlan';
    protected ?string $fieldName = 'id';

    public function __construct(
        LeavePlanService $service,
        protected LeavePlanService $planService
    ) {
        $this->service = $service;
    }

    public function store(LeavePlanRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $data = $request->validated();
            $data['created_by'] ??= auth()->id();
            $plan = $this->service->store($data);
            DB::commit();
            return response()->json($plan, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating leave plan', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(LeavePlanRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $plan = $this->service->update($request->validated(), $id);
            DB::commit();
            return response()->json($plan, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating leave plan', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function syncBalance(int $id)
    {
        try {
            return response()->json($this->planService->syncBalance($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error syncing balance', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function calendar(Request $request)
    {
        try {
            $year = $request->input('year', now()->year);
            $departmentId = $request->input('department_id');
            return response()->json($this->planService->calendar($year, $departmentId));
        } catch (Exception $e) {
            Log::error('Error fetching calendar', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
