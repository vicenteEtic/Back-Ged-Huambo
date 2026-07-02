<?php

namespace Database\Factories\RH\Career;

use App\Models\RH\Career\ProgressionRequest;
use App\Models\RH\Career\ProgressionRule;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgressionRequestFactory extends Factory
{
    protected $model = ProgressionRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'rule_id' => ProgressionRule::factory(),
            'type' => 'progression',
            'from_category' => 'Técnico',
            'to_category' => 'Senior',
            'current_salary' => fake()->randomFloat(2, 100000, 300000),
            'new_salary' => fake()->randomFloat(2, 150000, 400000),
            'status' => 'pending',
            'requested_by' => \App\Models\User::factory(),
        ];
    }
}
