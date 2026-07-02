<?php

namespace Database\Factories\RH\Training;

use App\Models\RH\Training\TrainingCourse;
use App\Models\RH\Training\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingSessionFactory extends Factory
{
    protected $model = TrainingSession::class;

    public function definition(): array
    {
        return [
            'course_id' => TrainingCourse::factory(),
            'start_date' => fake()->dateTimeBetween('now', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 months'),
            'location' => fake()->randomElement(['Sala A', 'Sala B', 'Auditório', 'Online']),
            'max_participants' => fake()->numberBetween(10, 50),
            'status' => 'scheduled',
        ];
    }
}
