<?php

namespace Database\Factories\RH\Performance;

use App\Models\RH\Performance\PerformanceCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceCycleFactory extends Factory
{
    protected $model = PerformanceCycle::class;

    public function definition(): array
    {
        $start = fake()->dateTimeThisYear();
        return [
            'name' => 'Ciclo ' . fake()->year() . ' - ' . fake()->randomElement(['Semestral', 'Anual']),
            'code' => strtoupper(fake()->unique()->lexify('CYC???')),
            'start_date' => $start,
            'end_date' => fake()->dateTimeBetween($start->format('Y-m-d'), '+6 months'),
            'status' => 'active',
        ];
    }
}
