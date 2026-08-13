<?php

namespace Database\Factories\RH\Attendance;

use App\Models\RH\Attendance\AbsenceJustification;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsenceJustificationFactory extends Factory
{
    protected $model = AbsenceJustification::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date' => fake()->date(),
            'absence_type' => fake()->randomElement(['doenca', 'luto', 'casamento', 'outro']),
            'reason' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'submitted_by' => null,
        ];
    }
}
