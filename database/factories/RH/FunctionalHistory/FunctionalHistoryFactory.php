<?php

namespace Database\Factories\RH\FunctionalHistory;

use App\Models\RH\Employee\Employee;
use App\Models\RH\FunctionalHistory\FunctionalHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class FunctionalHistoryFactory extends Factory
{
    protected $model = FunctionalHistory::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => fake()->randomElement(['appointment', 'promotion', 'progression', 'transfer', 'salary_change']),
            'previous_value' => ['salary' => fake()->randomFloat(2, 50000, 200000)],
            'new_value' => ['salary' => fake()->randomFloat(2, 100000, 500000)],
            'effective_date' => fake()->dateTimeThisYear(),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
