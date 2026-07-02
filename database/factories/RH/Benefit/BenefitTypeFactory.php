<?php

namespace Database\Factories\RH\Benefit;

use App\Models\RH\Benefit\BenefitType;
use Illuminate\Database\Eloquent\Factories\Factory;

class BenefitTypeFactory extends Factory
{
    protected $model = BenefitType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Seguro de Saúde', 'Subsídio de Transporte', 'Subsídio de Alimentação', 'Prémio de Desempenho']),
            'code' => strtoupper(fake()->unique()->lexify('BNF???')),
            'category' => fake()->randomElement(['subsidy', 'medical', 'social_support', 'institutional', 'other']),
            'description' => fake()->sentence(),
            'provider' => fake()->company(),
        ];
    }
}
