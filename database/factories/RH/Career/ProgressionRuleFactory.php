<?php

namespace Database\Factories\RH\Career;

use App\Models\RH\Career\ProgressionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgressionRuleFactory extends Factory
{
    protected $model = ProgressionRule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Progressão Técnica', 'Promoção Senior', 'Mudança de Categoria']),
            'code' => strtoupper(fake()->unique()->lexify('PRG???')),
            'type' => fake()->randomElement(['progression', 'promotion']),
            'description' => fake()->sentence(),
            'min_months_in_position' => 12,
            'min_performance_score' => 70,
            'min_level' => 1,
            'to_category' => 'Categoria Superior',
            'salary_increase_percent' => fake()->randomFloat(2, 5, 30),
            'is_active' => true,
        ];
    }
}
