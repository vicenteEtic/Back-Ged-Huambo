<?php

namespace Database\Factories\RH\Leave;

use App\Models\RH\Leave\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Férias Anuais', 'Licença Médica', 'Luto', 'Casamento', 'Paternidade', 'Maternidade']),
            'code' => strtoupper(fake()->unique()->lexify('LV???')),
            'description' => fake()->sentence(),
            'default_days' => fake()->numberBetween(5, 30),
            'allows_carryover' => fake()->boolean(),
            'max_carryover_days' => 10,
            'requires_attachment' => fake()->boolean(),
            'is_active' => true,
        ];
    }
}
