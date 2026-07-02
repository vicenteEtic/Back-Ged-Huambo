<?php

namespace Database\Factories\RH\Archive;

use App\Models\RH\Archive\ArchiveCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArchiveCategoryFactory extends Factory
{
    protected $model = ArchiveCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Processos Individuais', 'Documentos Administrativos',
                'Relatórios', 'Avaliações', 'Despachos',
            ]),
            'code' => strtoupper(fake()->unique()->lexify('ARC???')),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['processo_individual', 'administrativo', 'relatorio', 'avaliacao', 'despacho']),
            'is_active' => true,
        ];
    }

    public function child(): static
    {
        return $this->state(fn(array $attr) => [
            'parent_id' => ArchiveCategory::factory(),
        ]);
    }
}
