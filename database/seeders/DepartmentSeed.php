<?php

namespace Database\Seeders;

use App\Models\RH\Department\Department;
use Illuminate\Database\Seeder;

class DepartmentSeed extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Secretaria-Geral',                                  'code' => 'SEC-GERAL'],
            ['name' => 'Gabinete Jurídico e de Intercâmbio',                'code' => 'GAB-JUR'],
            ['name' => 'Gabinete de Comunicação Social',                    'code' => 'GAB-COM'],
            ['name' => 'Gabinete de Recursos Humanos',                      'code' => 'GAB-RH'],
            ['name' => 'Gabinete do Governador',                            'code' => 'GAB-GOV'],
            ['name' => 'Vice-Governador para o Sector Político, Social e Económico', 'code' => 'VICE-PSE'],
            ['name' => 'Vice-Governador para os Serviços Técnicos e Infraestruturas', 'code' => 'VICE-STI'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'description' => "Departamento de {$dept['name']}",
                    'is_active' => true,
                ]
            );

            $this->command->info("Departamento '{$dept['name']}' criado/actualizado.");
        }
    }
}
