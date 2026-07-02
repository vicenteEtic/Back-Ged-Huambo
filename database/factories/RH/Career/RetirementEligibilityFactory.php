<?php

namespace Database\Factories\RH\Career;

use App\Models\RH\Career\RetirementEligibility;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class RetirementEligibilityFactory extends Factory
{
    protected $model = RetirementEligibility::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'retirement_age' => 60,
            'contribution_years' => fake()->randomFloat(1, 5, 40),
            'minimum_contribution_years' => 15,
            'age_eligible' => false,
            'contribution_eligible' => fake()->boolean(),
        ];
    }
}
