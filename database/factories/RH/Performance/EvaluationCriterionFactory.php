<?php

namespace Database\Factories\RH\Performance;

use App\Models\RH\Performance\EvaluationCriterion;
use App\Models\RH\Performance\PerformanceCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationCriterionFactory extends Factory
{
    protected $model = EvaluationCriterion::class;

    public function definition(): array
    {
        return [
            'cycle_id' => PerformanceCycle::factory(),
            'name' => fake()->unique()->randomElement([
                'Produtividade', 'Pontualidade', 'Trabalho em Equipa',
                'Comunicação', 'Liderança', 'Iniciativa',
            ]),
            'description' => fake()->sentence(),
            'max_score' => 100,
            'weight' => fake()->numberBetween(10, 30),
        ];
    }
}
