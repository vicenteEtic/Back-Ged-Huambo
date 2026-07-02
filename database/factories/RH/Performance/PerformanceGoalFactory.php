<?php

namespace Database\Factories\RH\Performance;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Performance\PerformanceCycle;
use App\Models\RH\Performance\PerformanceGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceGoalFactory extends Factory
{
    protected $model = PerformanceGoal::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'cycle_id' => PerformanceCycle::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'target_value' => fake()->randomFloat(2, 50, 100),
            'actual_value' => null,
            'weight' => fake()->numberBetween(10, 100),
            'status' => 'pending',
        ];
    }
}
