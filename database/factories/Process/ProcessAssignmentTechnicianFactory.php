<?php

namespace Database\Factories\Process;

use App\Models\Process\ProcessAssignmentTechnician;
use App\Models\Process\ProcessAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessAssignmentTechnicianFactory extends Factory
{
    protected $model = ProcessAssignmentTechnician::class;

    public function definition(): array
    {
        return [
            'process_assignment_id' => ProcessAssignment::factory(),
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
            'file_path' => null,
            'file_type' => null,
            'file_size' => null,
            'mime_type' => null,
            'started_at' => null,
            'submitted_at' => null,
            'assigned_at' => now(),
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

    public function submitted(): static
    {
        return $this->state([
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
            'file_path' => 'uploads/results/' . fake()->uuid() . '.pdf',
            'file_type' => 'pdf',
            'file_size' => fake()->numberBetween(1024, 5242880),
            'mime_type' => 'application/pdf',
        ]);
    }

    public function validated(): static
    {
        return $this->state([
            'status' => 'validated',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);
    }
}
