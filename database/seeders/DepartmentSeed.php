<?php

namespace Database\Seeders;

use App\Models\RH\Department\Department;
use Illuminate\Database\Seeder;

class DepartmentSeed extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Expediente de Recursos Humanos',                      'code' => 'EXPED-RH',  'type' => 'expediente'],
            ['name' => 'Secretaria-Geral',                                    'code' => 'SEC-GERAL', 'type' => 'departamento'],
            ['name' => 'Gabinete Jurídico e de Intercâmbio',                  'code' => 'GAB-JUR',   'type' => 'gabinete'],
            ['name' => 'Gabinete de Comunicação Social',                      'code' => 'GAB-COM',   'type' => 'gabinete'],
            ['name' => 'Gabinete de Recursos Humanos',                        'code' => 'GAB-RH',    'type' => 'gabinete'],
            ['name' => 'Gabinete do Governador',                              'code' => 'GAB-GOV',   'type' => 'gabinete'],
            ['name' => 'Vice-Governador para o Sector Político, Social e Económico', 'code' => 'VICE-PSE', 'type' => 'vice_governador'],
            ['name' => 'Vice-Governador para os Serviços Técnicos e Infraestruturas', 'code' => 'VICE-STI', 'type' => 'vice_governador'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'type' => $dept['type'],
                    'description' => "Departamento de {$dept['name']}",
                    'is_active' => true,
                ]
            );

            $this->command->info("Departamento '{$dept['name']}' ({$dept['type']}) criado/actualizado.");
        }
    }
}
