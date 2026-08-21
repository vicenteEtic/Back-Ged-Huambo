<?php

namespace Database\Seeders;

use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use Illuminate\Database\Seeder;

class PositionSeed extends Seeder
{
    /**
     * Quadro de carreiras por categoria.
     * Formato: [categoria, nome, código, nível (ordem no quadro), salário base (Kz)]
     */
    public function run(): void
    {
        $positions = [
            // ASSESSOR
            ['ASSESSOR',         'Assessor Principal',                    'ASS-PRINCIPAL',    579055.80],
            ['ASSESSOR',         'Primeiro Assessor',                     'ASS-PRIMEIRO',     542864.82],
            ['ASSESSOR',         'Assessor',                              'ASS-1',            506673.83],

            // TÉCNICO SUPERIOR
            ['TÉCNICO SUPERIOR', 'Técnico Superior Principal',            'TSUP-PRINC',       0],
            ['TÉCNICO SUPERIOR', 'Técnico Superior de 1ª Classe',         'TSUP-C1',          0],
            ['TÉCNICO SUPERIOR', 'Técnico Superior de 2ª Classe',         'TSUP-C2',          0],
            ['TÉCNICO SUPERIOR', 'Especialista Principal',                'TSUP-ESP-PRINC',   325718.89],
            ['TÉCNICO SUPERIOR', 'Especialista de 1ª Classe',             'TSUP-ESP-C1',      289527.90],
            ['TÉCNICO SUPERIOR', 'Especialista de 2ª Classe',             'TSUP-ESP-C2',      253336.91],

            // TÉCNICO
            ['TÉCNICO',          'Técnico de 1ª Classe',                  'TEC-C1',           241273.25],
            ['TÉCNICO',          'Técnico de 2ª Classe',                  'TEC-C2',           223177.76],
            ['TÉCNICO',          'Técnico de 3ª Classe',                  'TEC-C3',           217145.93],

            // TÉCNICO MÉDIO
            ['TÉCNICO MÉDIO',    'Técnico Médio Principal de 1ª Classe',  'TMED-PRINC-C1',    211114.10],
            ['TÉCNICO MÉDIO',    'Técnico Médio Principal de 2ª Classe',  'TMED-PRINC-C2',    205082.26],
            ['TÉCNICO MÉDIO',    'Técnico Médio Principal de 3ª Classe',  'TMED-PRINC-C3',    180954.94],
            ['TÉCNICO MÉDIO',    'Técnico Médio de 1ª Classe',            'TMED-C1',          168891.28],
            ['TÉCNICO MÉDIO',    'Técnico Médio de 2ª Classe',            'TMED-C2',          156827.61],
            ['TÉCNICO MÉDIO',    'Técnico Médio de 3ª Classe',            'TMED-C3',          144763.95],

            // ADMINISTRATIVO
            ['ADMINISTRATIVO',   'Oficial Administrativo Principal',      'ADM-OFIC-PRINC',   141951.04],
            ['ADMINISTRATIVO',   '1º Oficial Administrativo',             'ADM-OFIC-1',       140727.33],
            ['ADMINISTRATIVO',   '2º Oficial Administrativo',             'ADM-OFIC-2',       132161.32],
            ['ADMINISTRATIVO',   '3º Oficial Administrativo',             'ADM-OFIC-3',       127266.45],
            ['ADMINISTRATIVO',   'Escriturário-Dactilógrafo',             'ADM-ESCRIT-DACT',  117476.73],

            // AUXILIAR
            ['AUXILIAR',         'Motorista de Pesados Principal',        'AUX-MOT-PES-PRINC', 132161.32],
            ['AUXILIAR',         'Motorista de Ligeiros de 1ª Classe',    'AUX-MOT-LIG-C1',    122371.59],
            ['AUXILIAR',         'Telefonista Principal',                 'AUX-TEL-PRINC',     117476.73],
            ['AUXILIAR',         'Telefonista Principal de 1ª Classe',    'AUX-TEL-PRINC-C1',  112581.86],
            ['AUXILIAR',         'Telefonista Principal de 2ª Classe',    'AUX-TEL-PRINC-C2',  107687.00],
            ['AUXILIAR',         'Auxiliar Administrativo Principal',     'AUX-ADM-PRINC',     112581.86],
            ['AUXILIAR',         'Auxiliar Administrativo de 1ª Classe',  'AUX-ADM-C1',        107687.00],
            ['AUXILIAR',         'Auxiliar Administrativo de 2ª Classe',  'AUX-ADM-C2',        102792.14],
            ['AUXILIAR',         'Auxiliar de Limpeza Principal',         'AUX-LIMP-PRINC',    107687.00],
            ['AUXILIAR',         'Auxiliar de Limpeza de 1ª Classe',      'AUX-LIMP-C1',       102792.14],
            ['AUXILIAR',         'Auxiliar de Limpeza de 2ª Classe',      'AUX-LIMP-C2',       97897.27],
        ];

        $departments = Department::pluck('id', 'code');
        $departmentId = $departments['SEC-GERAL'] ?? $departments->first();

        if (!$departmentId) {
            $this->command->warn('Nenhum departamento encontrado — cria os departamentos antes dos cargos.');
            return;
        }

        foreach ($positions as $i => [$category, $name, $code, $salary]) {
            Position::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'department_id' => $departmentId,
                    'level' => $i + 1,
                    'base_salary' => $salary,
                    'description' => "Categoria: {$category}",
                    'is_active' => true,
                ]
            );

            $this->command->info("Cargo '{$name}' criado/actualizado.");
        }

        // Remove cargos que não fazem parte do quadro actual
        $removed = Position::whereNotIn('code', array_column($positions, 2))->delete();
        if ($removed > 0) {
            $this->command->info("{$removed} cargo(s) fora do quadro removido(s).");
        }
    }
}
