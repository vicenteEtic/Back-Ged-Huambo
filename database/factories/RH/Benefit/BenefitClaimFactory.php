<?php

namespace Database\Factories\RH\Benefit;

use App\Models\RH\Benefit\BenefitClaim;
use App\Models\RH\Benefit\BenefitType;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class BenefitClaimFactory extends Factory
{
    protected $model = BenefitClaim::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'benefit_type_id' => BenefitType::factory(),
            'amount_requested' => fake()->randomFloat(2, 10000, 500000),
            'description' => fake()->sentence(),
            'status' => 'pending',
            'requested_date' => fake()->dateTimeThisYear(),
        ];
    }
}
