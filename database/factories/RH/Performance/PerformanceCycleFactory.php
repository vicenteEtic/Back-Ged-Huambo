<?php

namespace Database\Factories\RH\Performance;

use App\Models\RH\Performance\PerformanceCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceCycleFactory extends Factory
{
    protected $model = PerformanceCycle::class;

    public function definition(): array
    {
        return [
            'name' => 'Ciclo ' . fake()->year() . ' - ' . fake()->randomElement(['Semestral', 'Anual']),
            'code' => strtoupper(fake()->unique()->lexify('CYC???')),
            'description' => fake()->sentence(),
            'start_date' => fake()->dateTimeThisYear(),
            'end_date' => fake()->dateTimeThisYear('+6 months'),
            'status' => 'active',
        ];
    }
}
