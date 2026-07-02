<?php

namespace Database\Factories\RH\Department;

use App\Models\RH\Department\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'code' => strtoupper(fake()->unique()->lexify('DEPT???')),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
