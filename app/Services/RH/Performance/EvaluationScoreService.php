<?php

namespace App\Services\RH\Performance;

use App\Models\RH\Performance\EvaluationCriterion;
use App\Models\RH\Performance\PerformanceEvaluation;
use App\Repositories\RH\Performance\EvaluationScoreRepository;
use App\Services\AbstractService;
use Illuminate\Support\Facades\DB;

class EvaluationScoreService extends AbstractService
{
    public function __construct(EvaluationScoreRepository $repository)
    {
        parent::__construct($repository);
    }

    public function calculateOverall(int $evaluationId): PerformanceEvaluation
    {
        $evaluation = PerformanceEvaluation::with('scores.criterion')->findOrFail($evaluationId);
        $criteria = EvaluationCriterion::where('cycle_id', $evaluation->cycle_id)->where('is_active', true)->get();

        if ($criteria->isEmpty() || $evaluation->scores->isEmpty()) {
            return $evaluation;
        }

        $totalWeight = $criteria->sum('weight');
        $weightedSum = 0;

        foreach ($criteria as $criterion) {
            $score = $evaluation->scores->firstWhere('criterion_id', $criterion->id);
            if ($score && $totalWeight > 0) {
                $normalizedScore = ($score->score / $criterion->max_score) * 100;
                $weightedSum += $normalizedScore * ($criterion->weight / $totalWeight);
            }
        }

        $evaluation->overall_score = round($weightedSum, 2);
        $evaluation->save();

        return $evaluation->fresh();
    }
}
