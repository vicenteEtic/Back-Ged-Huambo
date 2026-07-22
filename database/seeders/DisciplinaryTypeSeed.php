<?php

namespace Database\Seeders;

use App\Models\RH\Disciplinary\DisciplinaryType;
use Illuminate\Database\Seeder;

class DisciplinaryTypeSeed extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Advertência Verbal',       'code' => 'ADV-VRB', 'severity' => 'low'],
            ['name' => 'Advertência Escrita',       'code' => 'ADV-ESC', 'severity' => 'low'],
            ['name' => 'Repreensão',                'code' => 'REPR',    'severity' => 'low'],
            ['name' => 'Multa',                     'code' => 'MULTA',   'severity' => 'medium'],
            ['name' => 'Suspensão de Curta Duração', 'code' => 'SUS-CD', 'severity' => 'medium'],
            ['name' => 'Suspensão de Longa Duração', 'code' => 'SUS-LD', 'severity' => 'high'],
            ['name' => 'Perda de Benefícios',       'code' => 'PERD-BF', 'severity' => 'high'],
            ['name' => 'Despedimento por Justa Causa', 'code' => 'DESP-JC', 'severity' => 'critical'],
            ['name' => 'Despedimento sem Justa Causa', 'code' => 'DESP-SC', 'severity' => 'critical'],
        ];

        foreach ($types as $type) {
            DisciplinaryType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'severity' => $type['severity'],
                    'description' => "Tipo disciplinar: {$type['name']}",
                    'is_active' => true,
                ]
            );

            $this->command->info("Tipo disciplinar '{$type['name']}' criado/actualizado.");
        }
    }
}
