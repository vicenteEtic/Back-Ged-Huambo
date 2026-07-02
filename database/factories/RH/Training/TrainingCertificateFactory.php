<?php

namespace Database\Factories\RH\Training;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Training\TrainingCertificate;
use App\Models\RH\Training\TrainingCourse;
use App\Models\RH\Training\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingCertificateFactory extends Factory
{
    protected $model = TrainingCertificate::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'course_id' => TrainingCourse::factory(),
            'session_id' => TrainingSession::factory(),
            'certificate_number' => 'CERT-' . fake()->unique()->numerify('########'),
            'issued_date' => fake()->dateTimeThisYear(),
            'expiry_date' => fake()->dateTimeBetween('+1 year', '+5 years'),
        ];
    }
}
