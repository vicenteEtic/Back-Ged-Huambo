<?php

namespace Database\Factories\Process;

use App\Models\Process\ProcessComment;
use App\Models\Process\Process;
use App\Models\Process\ProcessAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessCommentFactory extends Factory
{
    protected $model = ProcessComment::class;

    public function definition(): array
    {
        return [
            'process_id' => Process::factory(),
            'assignment_id' => null,
            'user_id' => User::factory(),
            'comment' => fake()->paragraph(2),
            'comment_type' => fake()->randomElement(['observation', 'instruction', 'correction', 'general']),
            'attachment_path' => null,
        ];
    }

    public function observation(): static
    {
        return $this->state(['comment_type' => 'observation']);
    }

    public function instruction(): static
    {
        return $this->state(['comment_type' => 'instruction']);
    }

    public function correction(): static
    {
        return $this->state(['comment_type' => 'correction']);
    }
}
