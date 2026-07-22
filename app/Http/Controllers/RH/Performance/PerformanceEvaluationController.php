<?php

namespace App\Http\Controllers\RH\Performance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Performance\PerformanceEvaluationRequest;
use App\Services\RH\Performance\PerformanceEvaluationService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PerformanceEvaluationController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Avaliação de Desempenho';
    protected ?string $fieldName = 'id';

    public function __construct(
        PerformanceEvaluationService $service,
        protected PerformanceEvaluationService $evaluationService,
    ) {
        $this->service = $service;
    }

    public function store(PerformanceEvaluationRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(PerformanceEvaluationRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function calculate(int $id)
    {
        try {
            $evaluation = $this->evaluationService->calculateOverall($id);
            return response()->json([
                'evaluation_id' => $evaluation->id,
                'overall_score' => $evaluation->overall_score,
                'classification' => $this->evaluationService->getClassification($evaluation->overall_score),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao calcular pontuação', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
