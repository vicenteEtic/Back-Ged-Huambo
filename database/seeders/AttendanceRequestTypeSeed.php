<?php

namespace Database\Seeders;

use App\Models\RH\Attendance\AttendanceRequestType;
use Illuminate\Database\Seeder;

class AttendanceRequestTypeSeed extends Seeder
{
    private const LEGAL_REFS = [
        'dispensa' => 'Lei n.º 26/22 (Lei de Bases da Função Pública)',
        'amamentacao' => 'Art. 93.º, n.ºs 2 e 3 da Lei n.º 26/22',
        'pre_natal' => 'Art. 93.º, n.º 1 da Lei n.º 26/22',
        'relatorio_medico' => 'Art. 90.º da Lei n.º 26/22',
        'acompanhamento_deficiencia' => 'Art. 96.º da Lei n.º 26/22',
        'outro' => 'Lei n.º 26/22 (Lei de Bases da Função Pública)',
    ];

    public function run(): void
    {
        $types = config('rh.dispensa.types', []);

        foreach ($types as $index => $type) {
            $code = $type['code'];

            AttendanceRequestType::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $type['name'],
                    'description' => $type['description'] ?? null,
                    'required_documents' => $type['required_documents'] ?? [],
                    'max_days' => $type['max_days'] ?? null,
                    'legal_ref' => self::LEGAL_REFS[$code] ?? null,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );

            $this->command->info("Tipo de solicitação de dispensa '{$type['name']}' criado/actualizado.");
        }
    }
}
