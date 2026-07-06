<?php

namespace Database\Factories\RH\Disciplinary;

use App\Models\RH\Disciplinary\DisciplinaryType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisciplinaryTypeFactory extends Factory
{
    protected $model = DisciplinaryType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Advertência Verbal', 'Advertência Escrita', 'Suspensão', 'Multa', 'Despedimento']),
            'code' => strtoupper(fake()->unique()->lexify('DSC???')),
            'description' => fake()->sentence(),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'is_active' => true,
        ];
    }
}
