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
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'grace_minutes' => 15,
            'duration_hours' => 9,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
