<?php

namespace App\Http\Controllers\RH\Performance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Performance\EvaluationScoreRequest;
use App\Services\RH\Performance\EvaluationScoreService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class EvaluationScoreController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Pontuação de Avaliação';
    protected ?string $fieldName = 'id';

    public function __construct(
        EvaluationScoreService $service,
        protected EvaluationScoreService $scoreService
    ) {
        $this->service = $service;
    }

    public function store(EvaluationScoreRequest $request)
    {
        return $this->handleStore(function () use ($request) {
            $score = $this->service->store($request->validated());
            $this->scoreService->calculateOverall($score->evaluation_id);
            return $score->load('criterion');
        });
    }

    public function update(EvaluationScoreRequest $request, $id)
    {
        return $this->handleUpdate(function () use ($request, $id) {
            $score = $this->service->update($request->validated(), $id);
            $this->scoreService->calculateOverall($score->evaluation_id);
            return $score->load('criterion');
        }, $id);
    }

    public function byEvaluation(int $evaluationId)
    {
        try {
            $evaluation = \App\Models\RH\Performance\PerformanceEvaluation::with('scores.criterion', 'cycle')->findOrFail($evaluationId);
            $criteria = \App\Models\RH\Performance\EvaluationCriterion::where('cycle_id', $evaluation->cycle_id)
                ->where('is_active', true)
                ->get();

            $scores = $evaluation->scores->keyBy('criterion_id');

            $result = $criteria->map(function ($c) use ($scores) {
                $score = $scores->get($c->id);
                return [
                    'criterion_id' => $c->id,
                    'criterion_name' => $c->name,
                    'section' => $c->section,
                    'weight' => $c->weight,
                    'max_score' => $c->max_score,
                    'score' => $score?->score,
                    'comment' => $score?->comment,
                    'score_id' => $score?->id,
                ];
            });

            return response()->json([
                'evaluation_id' => $evaluation->id,
                'cycle' => $evaluation->cycle?->name,
                'overall_score' => $evaluation->overall_score,
                'scores' => $result,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao buscar pontuações', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
