<?php

namespace Database\Factories\RH\Position;

use App\Models\RH\Position\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'type' => Position::TYPE_CARGO,
        ];
    }
}
