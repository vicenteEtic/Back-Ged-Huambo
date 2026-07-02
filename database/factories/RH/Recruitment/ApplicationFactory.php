<?php

namespace Database\Factories\RH\Recruitment;

use App\Models\RH\Recruitment\Application;
use App\Models\RH\Recruitment\Candidate;
use App\Models\RH\Recruitment\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'job_opening_id' => JobOpening::factory(),
            'candidate_id' => Candidate::factory(),
            'status' => 'submitted',
            'applied_date' => fake()->dateTimeThisYear(),
        ];
    }
}
