<?php

namespace Database\Factories\RH\Leave;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeaveRequest;
use App\Models\RH\Leave\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+3 months');
        $end = (clone $start)->modify('+' . fake()->numberBetween(1, 15) . ' days');

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_days' => fake()->numberBetween(1, 15),
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];
    }
}
