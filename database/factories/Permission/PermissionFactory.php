<?php

namespace Database\Factories\Permission;

use App\Models\Permission\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word() . '-' . fake()->randomElement(['show', 'create', 'edit', 'delete']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
