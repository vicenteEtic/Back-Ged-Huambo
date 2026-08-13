<?php

namespace Database\Seeders;

use App\Models\RH\Attendance\AbsenceType;
use Illuminate\Database\Seeder;

class AbsenceTypeSeed extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'doenca', 'name' => 'Doença', 'description' => 'Falta por doença com atestado médico.'],
            ['code' => 'luto', 'name' => 'Luto', 'description' => 'Falta por falecimento de familiar.'],
            ['code' => 'casamento', 'name' => 'Casamento', 'description' => 'Falta por casamento.'],
            ['code' => 'maternidade', 'name' => 'Maternidade', 'description' => 'Licença de maternidade.'],
            ['code' => 'paternidade', 'name' => 'Paternidade', 'description' => 'Licença de paternidade.'],
            ['code' => 'acidente_trabalho', 'name' => 'Acidente de Trabalho', 'description' => 'Falta por acidente de trabalho.'],
            ['code' => 'injustificada', 'name' => 'Injustificada', 'description' => 'Falta sem justificação válida.'],
            ['code' => 'outro', 'name' => 'Outro', 'description' => 'Outro tipo de falta.'],
        ];

        $codes = array_column($types, 'code');

        AbsenceType::whereNotIn('code', $codes)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        foreach ($types as $type) {
            AbsenceType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'is_active' => true,
                ]
            );

            $this->command->info("Tipo de falta '{$type['name']}' criado/actualizado.");
        }
    }
}
