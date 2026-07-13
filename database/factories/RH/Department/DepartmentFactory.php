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
            'type' => fake()->randomElement(['departamento', 'gabinete', 'vice_governador']),
            'code' => strtoupper(fake()->unique()->lexify('DEPT???')),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function expediente(): static
    {
        return $this->state([
            'name' => 'Expediente Central',
            'type' => 'expediente',
            'code' => 'EXPED-RH',
        ]);
    }

    public function gabinete(): static
    {
        return $this->state(['type' => 'gabinete']);
    }

    public function departamento(): static
    {
        return $this->state(['type' => 'departamento']);
    }

    public function viceGovernador(): static
    {
        return $this->state(['type' => 'vice_governador']);
    }
}
