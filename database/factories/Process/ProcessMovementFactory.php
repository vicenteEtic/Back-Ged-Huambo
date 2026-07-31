<?php

namespace Database\Factories\Process;

use App\Models\Process\ProcessMovement;
use App\Models\Process\Process;
use App\Models\RH\Department\Department;
use App\Models\RH\Area\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessMovementFactory extends Factory
{
    protected $model = ProcessMovement::class;

    public function definition(): array
    {
        return [
            'process_id' => Process::factory(),
            'from_department_id' => null,
            'from_area_id' => null,
            'to_department_id' => null,
            'to_area_id' => null,
            'from_user_id' => User::factory(),
            'to_user_id' => null,
            'movement_type' => fake()->randomElement([
                'reception',
                'dispatch_to_chief',
                'dispatch_to_areas',
                'add_technician',
                'make_public',
                'validation_chief',
                'validation_director',
                'correction',
                'rejection',
                'closure',
            ]),
            'notes' => fake()->optional()->sentence(),
            'attachment_path' => null,
        ];
    }

    public function reception(): static
    {
        return $this->state(['movement_type' => 'reception']);
    }

    public function dispatch(): static
    {
        return $this->state(['movement_type' => 'dispatch_to_chief']);
    }

    public function closure(): static
    {
        return $this->state(['movement_type' => 'closure']);
    }
}
