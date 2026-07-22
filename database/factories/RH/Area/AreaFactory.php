<?php

namespace Database\Factories\RH\Area;

use App\Models\RH\Area\Area;
use App\Models\RH\Department\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('AREA???')),
            'description' => fake()->sentence(),
            'responsible_id' => null,
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
