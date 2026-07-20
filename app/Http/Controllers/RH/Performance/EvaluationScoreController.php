<?php

namespace App\Http\Controllers\RH\Performance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Performance\EvaluationScoreRequest;
use App\Models\RH\Performance\EvaluationCriterion;
use App\Models\RH\Performance\PerformanceEvaluation;
use App\Services\RH\Performance\EvaluationScoreService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvaluationScoreController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'EvaluationScore';
    protected ?string $fieldName = 'id';

    public function __construct(
        EvaluationScoreService $service,
        protected EvaluationScoreService $scoreService
    ) {
        $this->service = $service;
    }

    public function store(EvaluationScoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $score = $this->service->store($request->validated());
            $this->scoreService->calculateOverall($score->evaluation_id);
            DB::commit();
            return response()->json($score->load('criterion'), Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error creating evaluation score', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(EvaluationScoreRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $score = $this->service->update($request->validated(), $id);
            $this->scoreService->calculateOverall($score->evaluation_id);
            DB::commit();
            return response()->json($score->load('criterion'), Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating evaluation score', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function byEvaluation(int $evaluationId)
    {
        try {
            $evaluation = PerformanceEvaluation::with('scores.criterion', 'cycle')->findOrFail($evaluationId);
            $criteria = EvaluationCriterion::where('cycle_id', $evaluation->cycle_id)
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
            Log::error('Error fetching scores', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
