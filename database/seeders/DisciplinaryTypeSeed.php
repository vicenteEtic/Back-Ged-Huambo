<?php

namespace Database\Seeders;

use App\Models\RH\Disciplinary\DisciplinaryType;
use Illuminate\Database\Seeder;

class DisciplinaryTypeSeed extends Seeder
{
    /**
     * Medidas disciplinares previstas no art. 123.º da Lei n.º 26/22
     * (Lei de Bases da Função Pública).
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Admoestação Verbal', 'code' => 'ADM-VRB', 'severity' => 'low', 'description' => 'Admoestação verbal (art. 123.º, alínea a) da Lei n.º 26/22).'],
            ['name' => 'Censura Registada', 'code' => 'CEN-REG', 'severity' => 'low', 'description' => 'Censura registada (art. 123.º, alínea b) da Lei n.º 26/22).'],
            ['name' => 'Multa', 'code' => 'MULTA', 'severity' => 'medium', 'description' => 'Multa (art. 123.º, alínea c) da Lei n.º 26/22).'],
            ['name' => 'Suspensão', 'code' => 'SUSP', 'severity' => 'high', 'description' => 'Suspensão (art. 123.º, alínea d) da Lei n.º 26/22).'],
            ['name' => 'Despromoção', 'code' => 'DESPROM', 'severity' => 'high', 'description' => 'Despromoção (art. 123.º, alínea e) da Lei n.º 26/22).'],
            ['name' => 'Demissão', 'code' => 'DEMIS', 'severity' => 'critical', 'description' => 'Demissão (art. 123.º, alínea f) da Lei n.º 26/22).'],
        ];

        $legalCodes = array_column($types, 'code');

        DisciplinaryType::whereNotIn('code', $legalCodes)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        foreach ($types as $type) {
            DisciplinaryType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'severity' => $type['severity'],
                    'description' => $type['description'],
                    'is_active' => true,
                ]
            );

            $this->command->info("Tipo disciplinar '{$type['name']}' criado/actualizado.");
        }
    }
}
