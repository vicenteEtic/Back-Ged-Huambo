<?php

namespace Database\Factories\RH\Declaration;

use App\Models\RH\Declaration\DeclarationRequest;
use App\Models\RH\Declaration\DeclarationType;
use App\Models\RH\Employee\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeclarationRequestFactory extends Factory
{
    protected $model = DeclarationRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'declaration_type_id' => DeclarationType::factory(),
            'institution_name' => fake()->optional()->company(),
            'institution_type' => fake()->optional()->randomElement(['banco', 'embaixada', 'instituicao_ensino', 'outro']),
            'purpose' => fake()->optional()->sentence(),
            'additional_info' => fake()->optional()->sentence(),
            'content' => ['title' => 'Conteúdo gerado', 'fields' => []],
            'status' => 'pending',
        ];
    }
}
