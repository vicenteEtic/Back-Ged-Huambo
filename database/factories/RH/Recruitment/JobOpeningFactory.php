<?php

namespace Database\Factories\RH\Recruitment;

use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\RH\Recruitment\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobOpeningFactory extends Factory
{
    protected $model = JobOpening::class;

    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'description' => fake()->paragraph(),
            'requirements' => fake()->paragraph(),
            'vacancies' => fake()->numberBetween(1, 5),
            'status' => 'open',
        ];
    }
}
