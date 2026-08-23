<?php

namespace Database\Seeders;

use App\Models\RH\Position\Position;
use Illuminate\Database\Seeder;

class PositionSeed extends Seeder
{
    /**
     * Cargos (funções exercidas / chefia) — distintos das categorias do quadro
     * (o quadro de carreiras é seedado pelo CategorySeed na tabela categories).
     * Cargos guardam apenas nome — o código é gerado automaticamente.
     */
    private const CARGOS = [
        'Governador',
        'Vice-Governador',
        'Director',
        'Chefe de Secção',
        'Chefe de Departamento',
        'Nenhum',
    ];

    public function run(): void
    {
        foreach (self::CARGOS as $name) {
            Position::updateOrCreate(
                ['name' => $name],
                [
                    'name' => $name,
                    'type' => Position::TYPE_CARGO,
                ]
            );

            $this->command->info("Cargo '{$name}' criado/actualizado.");
        }

        // Remove cargos fora da lista actual (soft delete — histórico preservado)
        $removed = Position::whereNotIn('name', self::CARGOS)->delete();
        if ($removed > 0) {
            $this->command->info("{$removed} cargo(s) fora da lista removido(s).");
        }
    }
}
