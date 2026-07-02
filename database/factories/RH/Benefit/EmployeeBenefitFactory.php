<?php

namespace Database\Factories\RH\Benefit;

use App\Models\RH\Benefit\BenefitType;
use App\Models\RH\Benefit\EmployeeBenefit;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeBenefitFactory extends Factory
{
    protected $model = EmployeeBenefit::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'benefit_type_id' => BenefitType::factory(),
            'amount' => fake()->randomFloat(2, 5000, 100000),
            'start_date' => fake()->dateTimeThisYear(),
            'end_date' => fake()->dateTimeThisYear('+1 year'),
            'status' => 'active',
        ];
    }
}
