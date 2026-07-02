<?php

namespace Database\Factories\RH\Recruitment;

use App\Models\RH\Recruitment\Application;
use App\Models\RH\Recruitment\Interview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'interview_date' => fake()->dateTimeBetween('now', '+2 months'),
            'interviewer_id' => User::factory(),
            'type' => fake()->randomElement(['presencial', 'online', 'telefonica']),
            'status' => 'scheduled',
            'notes' => fake()->sentence(),
        ];
    }
}
