<?php

namespace Database\Factories\RH\Performance;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Performance\PerformanceCycle;
use App\Models\RH\Performance\PerformanceEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceEvaluationFactory extends Factory
{
    protected $model = PerformanceEvaluation::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'cycle_id' => PerformanceCycle::factory(),
            'evaluator_id' => User::factory(),
            'overall_score' => null,
            'status' => 'pending',
        ];
    }
}
