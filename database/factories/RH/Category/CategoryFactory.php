<?php

namespace Database\Factories\RH\Category;

use App\Models\RH\Category\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'code' => strtoupper(fake()->unique()->lexify('CAT???')),
            'group' => fake()->randomElement(['ASSESSOR', 'TÉCNICO SUPERIOR', 'TÉCNICO', 'TÉCNICO MÉDIO', 'ADMINISTRATIVO', 'AUXILIAR']),
            'level' => fake()->numberBetween(1, 34),
            'base_salary' => fake()->randomFloat(2, 90000, 600000),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
