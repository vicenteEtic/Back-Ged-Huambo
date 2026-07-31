<?php

namespace Database\Factories\Process;

use App\Models\Process\ProcessAssignment;
use App\Models\Process\Process;
use App\Models\RH\Department\Department;
use App\Models\RH\Area\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessAssignmentFactory extends Factory
{
    protected $model = ProcessAssignment::class;

    public function definition(): array
    {
        return [
            'process_id' => Process::factory(),
            'department_id' => Department::factory(),
            'area_id' => null,
            'assigned_by' => User::factory(),
            'visibility' => fake()->randomElement(['public', 'private']),
            'status' => 'pending',
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'deadline' => fake()->optional()->dateTimeBetween('+7 days', '+60 days'),
            'notes' => fake()->optional()->sentence(),
            'result_notes' => null,
            'result_file_path' => null,
            'result_file_type' => null,
            'result_file_size' => null,
            'result_mime_type' => null,
            'started_at' => null,
            'completed_at' => null,
            'validated_at' => null,
            'validated_by' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function processing(): static
    {
        return $this->state([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function pendingValidation(): static
    {
        return $this->state([
            'status' => 'pending_validation',
            'completed_at' => now(),
        ]);
    }

    public function validated(): static
    {
        return $this->state([
            'status' => 'validated',
            'completed_at' => now(),
            'validated_at' => now(),
            'validated_by' => User::factory(),
        ]);
    }

    public function public(): static
    {
        return $this->state(['visibility' => 'public']);
    }

    public function private(): static
    {
        return $this->state(['visibility' => 'private']);
    }
}
