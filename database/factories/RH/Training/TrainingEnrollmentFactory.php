<?php

namespace Database\Factories\RH\Training;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Training\TrainingEnrollment;
use App\Models\RH\Training\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingEnrollmentFactory extends Factory
{
    protected $model = TrainingEnrollment::class;

    public function definition(): array
    {
        return [
            'session_id' => TrainingSession::factory(),
            'employee_id' => Employee::factory(),
            'status' => 'enrolled',
            'enrolled_date' => fake()->dateTimeThisYear(),
        ];
    }
}
