<?php

namespace Database\Factories\RH\Position;

use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'name' => fake()->jobTitle(),
            'code' => strtoupper(fake()->unique()->lexify('POS???')),
            'description' => fake()->sentence(),
            'department_id' => Department::factory(),
            'level' => fake()->numberBetween(1, 10),
            'base_salary' => fake()->randomFloat(2, 50000, 500000),
            'requirements' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
