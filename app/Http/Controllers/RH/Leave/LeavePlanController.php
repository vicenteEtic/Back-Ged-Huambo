<?php

namespace App\Http\Controllers\RH\Leave;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Leave\LeavePlanRequest;
use App\Services\RH\Leave\LeavePlanService;

class LeavePlanController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Plano de Férias';
    protected ?string $fieldName = 'id';

    public function __construct(
        LeavePlanService $service,
        protected LeavePlanService $planService
    ) {
        $this->service = $service;
    }

    public function store(LeavePlanRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] ??= auth()->id();
            return $this->service->store($data);
        });
    }

    public function update(LeavePlanRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function syncBalance(int $id)
    {
        try {
            return response()->json($this->planService->syncBalance($id));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao sincronizar saldo', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function calendar(\Illuminate\Http\Request $request)
    {
        try {
            $year = $request->input('year', now()->year);
            $departmentId = $request->input('department_id');
            return response()->json($this->planService->calendar($year, $departmentId));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao obter calendário de férias', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
