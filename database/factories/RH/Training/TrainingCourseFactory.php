<?php

namespace Database\Factories\RH\Training;

use App\Models\RH\Training\TrainingCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingCourseFactory extends Factory
{
    protected $model = TrainingCourse::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Liderança e Gestão', 'Comunicação Efectiva',
                'Excel Avançado', 'Gestão de Projectos',
                'Atendimento ao Cliente', 'Segurança no Trabalho',
            ]),
            'code' => strtoupper(fake()->unique()->lexify('TRN???')),
            'description' => fake()->paragraph(),
            'duration_hours' => fake()->numberBetween(4, 80),
            'provider' => fake()->company(),
            'is_active' => true,
        ];
    }
}
