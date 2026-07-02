<?php

namespace Database\Factories\RH\Archive;

use App\Models\RH\Archive\ArchiveCategory;
use App\Models\RH\Archive\ArchiveDocument;
use App\Models\RH\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArchiveDocumentFactory extends Factory
{
    protected $model = ArchiveDocument::class;

    public function definition(): array
    {
        return [
            'category_id' => ArchiveCategory::factory(),
            'employee_id' => Employee::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'document_number' => fake()->unique()->numerify('DOC-####'),
            'reference_number' => fake()->unique()->numerify('REF-####'),
            'issuing_authority' => fake()->company(),
            'file_path' => 'archives/' . fake()->uuid() . '.pdf',
            'file_type' => 'pdf',
            'file_size' => fake()->numberBetween(10000, 5000000),
            'mime_type' => 'application/pdf',
            'status' => 'draft',
            'confidentiality' => 'internal',
            'issued_date' => fake()->dateTimeThisYear(),
            'expiry_date' => fake()->dateTimeBetween('+1 year', '+5 years'),
            'is_physical_copy' => false,
            'created_by' => User::factory(),
        ];
    }
}
