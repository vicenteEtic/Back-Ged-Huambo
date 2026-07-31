<?php

namespace Database\Factories\Process;

use App\Models\Process\Process;
use App\Models\RH\Department\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessFactory extends Factory
{
    protected $model = Process::class;

    public function definition(): array
    {
        $expediente = Department::where('type', 'expediente')->first();

        return [
            'process_type' => fake()->randomElement(['external', 'internal']),
            'sequence_number' => strtoupper(fake()->lexify('????-??') . '/' . date('Y') . '/' . fake()->numerify('####')),
            'reception_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'reception_time' => fake()->time('H:i'),
            'reference_number' => strtoupper(fake()->lexify('????-??') . '/' . date('Y') . '/' . fake()->numerify('####')),
            'document_date' => fake()->dateTimeBetween('-60 days', '-30 days'),
            'subject' => fake()->sentence(6),
            'notes' => fake()->optional()->sentence(),
            'sender_entity' => fake()->company(),
            'file_path' => 'uploads/documents/' . fake()->uuid() . '.pdf',
            'file_type' => 'pdf',
            'file_size' => fake()->numberBetween(1024, 10485760),
            'mime_type' => 'application/pdf',
            'justification' => fake()->optional()->sentence(),
            'classification' => fake()->randomElement(['pedido', 'reclamacao', 'sugestao', 'informacao', 'outro']),
            'deadline' => fake()->optional()->dateTimeBetween('+7 days', '+60 days'),
            'status' => 'received',
            'current_department_id' => $expediente?->id ?? Department::factory(),
            'current_holder_id' => User::factory(),
            'origin_department_id' => Department::factory(),
            'origin_area_id' => null,
            'target_department_id' => null,
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'received_by' => User::factory(),
            'created_by' => User::factory(),
        ];
    }

    public function external(): static
    {
        return $this->state(['process_type' => 'external']);
    }

    public function internal(): static
    {
        return $this->state(['process_type' => 'internal']);
    }

    public function received(): static
    {
        return $this->state(['status' => 'received']);
    }

    public function dispatchedToChief(): static
    {
        return $this->state(['status' => 'dispatched_to_chief']);
    }

    public function dispatchedToAreas(): static
    {
        return $this->state(['status' => 'dispatched_to_areas']);
    }

    public function processing(): static
    {
        return $this->state(['status' => 'processing']);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }
}
