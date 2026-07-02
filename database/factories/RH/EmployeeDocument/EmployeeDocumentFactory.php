<?php

namespace Database\Factories\RH\EmployeeDocument;

use App\Models\RH\Employee\Employee;
use App\Models\RH\EmployeeDocument\EmployeeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeDocumentFactory extends Factory
{
    protected $model = EmployeeDocument::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'name' => fake()->words(3, true),
            'document_type' => fake()->randomElement(['bi', 'passport', 'certificate', 'contract', 'diploma']),
            'description' => fake()->sentence(),
            'file_path' => 'documents/' . fake()->uuid() . '.pdf',
            'expiry_date' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'is_verified' => fake()->boolean(),
        ];
    }

    public function expiring(): static
    {
        return $this->state(fn(array $attr) => [
            'expiry_date' => fake()->dateTimeBetween('now', '+30 days'),
            'is_verified' => false,
        ]);
    }
}
