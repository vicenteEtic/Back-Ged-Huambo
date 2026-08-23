<?php

namespace Database\Seeders;

use App\Models\RH\Category\Category;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use Illuminate\Database\Seeder;

class PositionSeed extends Seeder
{
    /**
     * Cargos (funções exercidas / chefia) — distintos das categorias do quadro
     * (o quadro de carreiras é seedado pelo CategorySeed na tabela categories).
     * Formato: [nome, código]
     */
    private const CARGOS = [
        ['Governador',              'CAR-GOVS'],
        ['Vice-Governador',         'CAR-VICE-GOVS'],
        ['Director',                'CAR-DIR'],
        ['Chefe de Secção',         'CAR-CHEF-SEC'],
        ['Chefe de Departamento',   'CAR-CHEF-DEP'],
        ['Nenhum',                  'CAR-NENHUM'],
    ];

    public function run(): void
    {
        $departments = Department::pluck('id', 'code');
        $departmentId = $departments['SEC-GERAL'] ?? $departments->first();

        if (!$departmentId) {
            $this->command->warn('Nenhum departamento encontrado — cria os departamentos antes dos cargos.');
            return;
        }

        foreach (self::CARGOS as $i => [$name, $code]) {
            Position::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => Position::TYPE_CARGO,
                    'department_id' => $departmentId,
                    'level' => $i + 1,
                    'base_salary' => 0,
                    'description' => 'Cargo funcional',
                    'is_active' => true,
                ]
            );

            $this->command->info("Cargo '{$name}' criado/actualizado.");
        }

        // Remove cargos fora da lista actual (soft delete — histórico preservado)
        $removed = Position::where('type', Position::TYPE_CARGO)
            ->whereNotIn('code', array_column(self::CARGOS, 1))
            ->delete();
        if ($removed > 0) {
            $this->command->info("{$removed} cargo(s) fora da lista removido(s).");
        }
    }
}
