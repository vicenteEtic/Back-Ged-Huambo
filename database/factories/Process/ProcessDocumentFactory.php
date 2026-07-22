<?php

namespace Database\Factories\Process;

use App\Models\Process\ProcessDocument;
use App\Models\Process\Process;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessDocumentFactory extends Factory
{
    protected $model = ProcessDocument::class;

    public function definition(): array
    {
        $fileType = fake()->randomElement(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg']);

        return [
            'process_id' => Process::factory(),
            'document_type' => fake()->randomElement([
                'original',
                'complementary',
                'response',
                'report',
                'attachment',
                'other',
            ]),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'file_path' => 'uploads/processes/' . fake()->uuid() . '.' . $fileType,
            'file_type' => $fileType,
            'file_size' => fake()->numberBetween(1024, 10485760),
            'mime_type' => match ($fileType) {
                'pdf' => 'application/pdf',
                'doc', 'docx' => 'application/msword',
                'xls', 'xlsx' => 'application/vnd.ms-excel',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                default => 'application/octet-stream',
            },
            'uploaded_by' => User::factory(),
        ];
    }
}
