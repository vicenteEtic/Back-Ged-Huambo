<?php

namespace Database\Factories\RH\Performance;

use App\Models\RH\Performance\EvaluationCriterion;
use App\Models\RH\Performance\EvaluationScore;
use App\Models\RH\Performance\PerformanceEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationScoreFactory extends Factory
{
    protected $model = EvaluationScore::class;

    public function definition(): array
    {
        return [
            'evaluation_id' => PerformanceEvaluation::factory(),
            'criterion_id' => EvaluationCriterion::factory(),
            'score' => fake()->numberBetween(50, 100),
            'comment' => fake()->sentence(),
        ];
    }
}
