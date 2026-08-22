<?php

namespace Database\Seeders;

use App\Models\RH\Category\Category;
use Illuminate\Database\Seeder;

class CategorySeed extends Seeder
{
    /**
     * Quadro de carreiras por categoria.
     * Formato: [grupo, nome, código, nível (ordem no quadro), salário base (Kz)]
     */
    private const QUADRO = [
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

    public function run(): void
    {
        foreach (self::QUADRO as $i => [$group, $name, $code, $salary]) {
            Category::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'group' => $group,
                    'level' => $i + 1,
                    'base_salary' => $salary,
                    'is_active' => true,
                ]
            );

            $this->command->info("Categoria '{$name}' criada/actualizada.");
        }

        // Remove categorias fora do quadro actual
        $removed = Category::whereNotIn('code', array_column(self::QUADRO, 2))->delete();
        if ($removed > 0) {
            $this->command->info("{$removed} categoria(s) fora do quadro removida(s).");
        }
    }
}
