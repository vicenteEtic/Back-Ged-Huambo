<?php

namespace Database\Factories\RH\Attendance;

use App\Models\RH\Attendance\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Normal', 'Nocturno', 'Fim-de-semana']),
            'code' => strtoupper(fake()->unique()->lexify('SFT???')),
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'grace_minutes' => 15,
            'tolerance_minutes' => 30,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
