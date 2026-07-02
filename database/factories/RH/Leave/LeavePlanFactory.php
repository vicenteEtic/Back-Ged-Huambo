<?php

namespace Database\Factories\RH\Leave;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeavePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeavePlanFactory extends Factory
{
    protected $model = LeavePlan::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'year' => now()->year,
            'total_days_entitled' => 22,
            'days_used' => 0,
            'days_pending' => 0,
            'days_remaining' => 22,
        ];
    }
}
