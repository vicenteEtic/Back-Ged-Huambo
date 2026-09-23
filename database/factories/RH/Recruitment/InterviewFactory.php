<?php

namespace Database\Factories\RH\Recruitment;

use App\Models\RH\Recruitment\Application;
use App\Models\RH\Recruitment\Interview;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 months'),
            'interviewer_id' => Employee::factory(),
            'type' => fake()->randomElement(['presencial', 'online', 'telefonica']),
            'status' => 'scheduled',
            'notes' => fake()->sentence(),
        ];
    }
}
