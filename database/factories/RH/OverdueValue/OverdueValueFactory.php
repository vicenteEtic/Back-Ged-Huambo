<?php

namespace Database\Factories\RH\OverdueValue;

use App\Models\RH\Employee\Employee;
use App\Models\RH\OverdueValue\OverdueValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class OverdueValueFactory extends Factory
{
    protected $model = OverdueValue::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => fake()->randomElement(['receivable', 'payable']),
            'description' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'reference_number' => fake()->numerify('DOC-####'),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => \App\Models\User::factory(),
        ];
    }
}
