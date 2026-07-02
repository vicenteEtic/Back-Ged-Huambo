<?php

namespace Database\Factories\RH\Benefit;

use App\Models\RH\Benefit\MedicalAssistance;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalAssistanceFactory extends Factory
{
    protected $model = MedicalAssistance::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'assistance_type' => fake()->randomElement(['consulta', 'exame', 'internamento', 'medicamento', 'cirurgia']),
            'provider' => fake()->company(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 1000, 200000),
            'assistance_date' => fake()->dateTimeThisYear(),
            'status' => 'pending',
        ];
    }
}
