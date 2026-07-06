<?php

namespace Database\Factories\RH\Attendance;

use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Attendance\Shift;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'shift_id' => Shift::factory(),
            'date' => fake()->dateTimeThisMonth(),
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'expected_check_in' => '08:00:00',
            'expected_check_out' => '17:00:00',
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'hours_worked' => 9.0,
            'status' => 'present',
        ];
    }

    public function late(): static
    {
        return $this->state(fn(array $attr) => [
            'check_in' => '08:45',
            'late_minutes' => 30,
            'status' => 'late',
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn(array $attr) => [
            'check_in' => null,
            'check_out' => null,
            'status' => 'absent',
            'absence_type' => fake()->randomElement(['justified', 'unjustified']),
            'absence_reason' => fake()->sentence(),
        ]);
    }
}
