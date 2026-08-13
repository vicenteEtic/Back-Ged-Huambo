<?php

namespace Database\Factories\RH\Attendance;

use App\Models\RH\Attendance\AbsenceType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsenceTypeFactory extends Factory
{
    protected $model = AbsenceType::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(1),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
