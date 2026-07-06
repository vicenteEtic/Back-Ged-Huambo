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
            'name' => fake()->randomElement(['Progressão Técnica', 'Promoção Senior', 'Mudança de Categoria', 'Promoção por Mérito', 'Progressão por Tempo de Serviço', 'Mudança de Nível']),
            'code' => strtoupper(fake()->unique()->lexify('PRG???')),
            'type' => fake()->randomElement(['progression', 'promotion']),
            'description' => fake()->sentence(),
            'min_months_in_category' => 12,
            'min_performance_score' => 70,
            'requires_training' => false,
            'requires_evaluation' => true,
            'to_category' => 'Categoria Superior',
            'salary_increase_percent' => fake()->randomFloat(2, 5, 30),
            'is_active' => true,
        ];
    }
}
