<?php

namespace Database\Factories\RH\Career;

use App\Models\RH\Career\PostRetirementHistory;
use App\Models\RH\Career\RetirementProcess;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostRetirementHistoryFactory extends Factory
{
    protected $model = PostRetirementHistory::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'retirement_process_id' => RetirementProcess::factory(),
            'record_date' => fake()->dateTimeThisYear(),
            'type' => fake()->randomElement(['pension_payment', 'update', 'document', 'note']),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 50000, 300000),
        ];
    }
}
