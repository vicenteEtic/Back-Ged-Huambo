<?php

namespace Database\Seeders;

use App\Models\RH\EmployeeDocument\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeed extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'BI',
                'name' => 'Bilhete de Identidade',
                'has_number' => true,
                'has_issue_date' => true,
                'has_expiry_date' => true,
                'has_place_of_issue' => true,
                'description' => 'Bilhete de Identidade (BI).',
            ],
            [
                'code' => 'PASSAPORTE',
                'name' => 'Passaporte',
                'has_number' => true,
                'has_issue_date' => true,
                'has_expiry_date' => true,
                'has_place_of_issue' => true,
                'description' => 'Passaporte.',
            ],
            [
                'code' => 'CARTA-CONDUCAO',
                'name' => 'Carta de Condução',
                'has_number' => true,
                'has_issue_date' => true,
                'has_expiry_date' => true,
                'has_place_of_issue' => true,
                'description' => 'Carta de Condução.',
            ],
            [
                'code' => 'NIF',
                'name' => 'NIF',
                'has_number' => true,
                'has_issue_date' => false,
                'has_expiry_date' => false,
                'has_place_of_issue' => false,
                'description' => 'Número de Identificação Fiscal (NIF).',
            ],
            [
                'code' => 'CONTRATO',
                'name' => 'Contrato de Trabalho',
                'has_number' => false,
                'has_issue_date' => true,
                'has_expiry_date' => true,
                'has_place_of_issue' => false,
                'description' => 'Contrato de trabalho.',
            ],
            [
                'code' => 'CERTIFICADO-HABILITACOES',
                'name' => 'Certificado de Habilitações',
                'has_number' => true,
                'has_issue_date' => true,
                'has_expiry_date' => false,
                'has_place_of_issue' => false,
                'description' => 'Certificado de habilitações literárias.',
            ],
            [
                'code' => 'DIPLOMA',
                'name' => 'Diploma',
                'has_number' => true,
                'has_issue_date' => true,
                'has_expiry_date' => false,
                'has_place_of_issue' => false,
                'description' => 'Diploma de qualificação académica.',
            ],
            [
                'code' => 'CERTIFICADO-FORMACAO',
                'name' => 'Certificado de Formação',
                'has_number' => true,
                'has_issue_date' => true,
                'has_expiry_date' => true,
                'has_place_of_issue' => false,
                'description' => 'Certificado de formação profissional.',
            ],
            [
                'code' => 'ATESTADO-MEDICO',
                'name' => 'Atestado Médico',
                'has_number' => false,
                'has_issue_date' => true,
                'has_expiry_date' => false,
                'has_place_of_issue' => false,
                'description' => 'Atestado ou declaração médica.',
            ],
            [
                'code' => 'CARTEIRA-PROFISSIONAL',
                'name' => 'Carteira Profissional',
                'has_number' => true,
                'has_issue_date' => true,
                'has_expiry_date' => true,
                'has_place_of_issue' => true,
                'description' => 'Carteira profissional da ordem/associação.',
            ],
            [
                'code' => 'OUTRO',
                'name' => 'Outro',
                'has_number' => false,
                'has_issue_date' => false,
                'has_expiry_date' => false,
                'has_place_of_issue' => false,
                'description' => 'Outro tipo de documento.',
            ],
        ];

        $codes = array_column($types, 'code');

        DocumentType::whereNotIn('code', $codes)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        foreach ($types as $type) {
            DocumentType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'has_number' => $type['has_number'],
                    'has_issue_date' => $type['has_issue_date'],
                    'has_expiry_date' => $type['has_expiry_date'],
                    'has_place_of_issue' => $type['has_place_of_issue'],
                    'description' => $type['description'],
                    'is_active' => true,
                ]
            );

            $this->command->info("Tipo de documento '{$type['name']}' criado/actualizado.");
        }
    }
}
