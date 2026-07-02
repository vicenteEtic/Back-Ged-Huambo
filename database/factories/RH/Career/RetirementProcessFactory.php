<?php

namespace Database\Factories\RH\Career;

use App\Models\RH\Career\RetirementProcess;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class RetirementProcessFactory extends Factory
{
    protected $model = RetirementProcess::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'request_date' => fake()->dateTimeThisYear(),
            'effective_date' => fake()->dateTimeThisYear('+6 months'),
            'status' => 'draft',
            'final_salary' => fake()->randomFloat(2, 200000, 800000),
            'pension_amount' => fake()->randomFloat(2, 100000, 400000),
            'pension_type' => fake()->randomElement(['pensão_velhice', 'pensão_invalidez', 'pensão_sobrevivência']),
        ];
    }
}
