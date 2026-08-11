<?php

namespace Database\Factories\RH\EmployeeDocument;

use App\Models\RH\EmployeeDocument\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('DOC-????')),
            'name' => fake()->words(3, true),
            'has_number' => true,
            'has_issue_date' => false,
            'has_expiry_date' => false,
            'has_place_of_issue' => false,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function withValidity(): static
    {
        return $this->state(fn (array $attr) => [
            'has_issue_date' => true,
            'has_expiry_date' => true,
            'has_place_of_issue' => true,
        ]);
    }
}
