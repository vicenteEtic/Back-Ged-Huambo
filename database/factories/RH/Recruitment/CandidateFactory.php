<?php

namespace Database\Factories\RH\Recruitment;

use App\Models\RH\Recruitment\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'document_type' => 'bi',
            'document_number' => fake()->unique()->numerify('###########'),
        ];
    }
}
