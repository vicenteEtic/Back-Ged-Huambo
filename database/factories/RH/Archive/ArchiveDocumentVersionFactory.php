<?php

namespace Database\Factories\RH\Archive;

use App\Models\RH\Archive\ArchiveDocument;
use App\Models\RH\Archive\ArchiveDocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArchiveDocumentVersionFactory extends Factory
{
    protected $model = ArchiveDocumentVersion::class;

    public function definition(): array
    {
        return [
            'archive_document_id' => ArchiveDocument::factory(),
            'version_number' => 1,
            'file_path' => 'archives/versions/' . fake()->uuid() . '.pdf',
            'file_size' => fake()->numberBetween(10000, 5000000),
            'mime_type' => 'application/pdf',
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
