<?php

namespace Database\Factories\RH\Training;

use App\Models\RH\Training\TrainingCertificate;
use App\Models\RH\Training\TrainingEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingCertificateFactory extends Factory
{
    protected $model = TrainingCertificate::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => TrainingEnrollment::factory(),
            'certificate_number' => 'CERT-' . fake()->unique()->numerify('########'),
            'issued_at' => fake()->dateTimeThisYear(),
            'expiry_date' => fake()->dateTimeBetween('+1 year', '+5 years'),
        ];
    }
}
