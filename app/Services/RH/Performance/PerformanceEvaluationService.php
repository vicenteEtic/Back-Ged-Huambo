<?php

namespace App\Services\RH\Performance;

use App\Models\RH\Performance\EvaluationCriterion;
use App\Models\RH\Performance\EvaluationScore;
use App\Models\RH\Performance\PerformanceEvaluation;
use App\Repositories\RH\Performance\PerformanceEvaluationRepository;
use App\Services\AbstractService;

class PerformanceEvaluationService extends AbstractService
{
    public function __construct(PerformanceEvaluationRepository $repository)
    {
        parent::__construct($repository);
    }

    public function calculateOverall(int $evaluationId): PerformanceEvaluation
    {
        $evaluation = PerformanceEvaluation::with('scores.criterion')->findOrFail($evaluationId);
        $criteria = EvaluationCriterion::where('cycle_id', $evaluation->cycle_id)
            ->where('is_active', true)
            ->get();

        if ($criteria->isEmpty() || $evaluation->scores->isEmpty()) {
            return $evaluation;
        }

        $totalWeight = $criteria->sum('weight');
        if ($totalWeight <= 0) return $evaluation;

        $weightedSum = 0;
        foreach ($criteria as $criterion) {
            $score = $evaluation->scores->firstWhere('criterion_id', $criterion->id);
            if ($score) {
                $normalizedScore = ($score->score / max($criterion->max_score, 1)) * 100;
                $weightedSum += $normalizedScore * ($criterion->weight / $totalWeight);
            }
        }

        $evaluation->overall_score = round($weightedSum, 2);
        $evaluation->save();

        return $evaluation->fresh();
    }

    public function getClassification(?float $score): string
    {
        return match (true) {
            $score >= 90 => 'Excelente',
            $score >= 75 => 'Bom',
            $score >= 60 => 'Satisfatório',
            $score >= 40 => 'Suficiente',
            $score >= 0  => 'Insuficiente',
            default       => 'Sem classificação',
        };
    }

    public function listByEmployee(int $employeeId, array $filters = []): array
    {
        $query = PerformanceEvaluation::where('employee_id', $employeeId)
            ->with('cycle');

        if (!empty($filters['cycle_id'])) {
            $query->where('cycle_id', $filters['cycle_id']);
        }

        $evaluations = $query->orderByDesc('created_at')->get();

        return $evaluations->map(function ($e) {
            return [
                'id' => $e->id,
                'cycle' => $e->cycle?->name,
                'overall_score' => $e->overall_score,
                'classification' => $this->getClassification($e->overall_score),
                'status' => $e->status,
                'submitted_at' => $e->submitted_at?->format('Y-m-d'),
            ];
        })->toArray();
    }
}
