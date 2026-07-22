<?php

namespace Database\Seeders;

use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use Illuminate\Database\Seeder;

class PositionSeed extends Seeder
{
    public function run(): void
    {
        $departments = Department::pluck('id', 'code');

        $positions = [
            ['name' => 'Governador Provincial',                        'code' => 'GOV-PROV',  'level' => 1,  'dept' => 'GAB-GOV'],
            ['name' => 'Vice-Governador',                              'code' => 'VICE-GOV',  'level' => 2,  'dept' => 'GAB-GOV'],
            ['name' => 'Secretário-Geral',                             'code' => 'SEC-GERAL', 'level' => 3,  'dept' => 'SEC-GERAL'],
            ['name' => 'Diretor de Gabinete',                          'code' => 'DIR-GAB',   'level' => 4,  'dept' => 'GAB-GOV'],
            ['name' => 'Diretor Provincial',                           'code' => 'DIR-PROV',  'level' => 5,  'dept' => 'SEC-GERAL'],
            ['name' => 'Diretor Nacional (quando destacado)',          'code' => 'DIR-NAC',   'level' => 5,  'dept' => 'SEC-GERAL'],
            ['name' => 'Chefe de Departamento',                        'code' => 'CHEF-DEP',  'level' => 6,  'dept' => 'SEC-GERAL'],
            ['name' => 'Chefe de Secção',                              'code' => 'CHEF-SEC',  'level' => 7,  'dept' => 'SEC-GERAL'],
            ['name' => 'Técnico Superior',                             'code' => 'TEC-SUP',   'level' => 8,  'dept' => 'SEC-GERAL'],
            ['name' => 'Técnico Médio',                                'code' => 'TEC-MED',   'level' => 9,  'dept' => 'SEC-GERAL'],
            ['name' => 'Assistente Técnico',                           'code' => 'ASS-TEC',   'level' => 10, 'dept' => 'SEC-GERAL'],
            ['name' => 'Assistente Administrativo',                    'code' => 'ASS-ADM',   'level' => 10, 'dept' => 'SEC-GERAL'],
            ['name' => 'Escriturário',                                 'code' => 'ESCRIT',    'level' => 11, 'dept' => 'SEC-GERAL'],
            ['name' => 'Motorista',                                    'code' => 'MOTOR',     'level' => 11, 'dept' => 'SEC-GERAL'],
            ['name' => 'Auxiliar Administrativo',                      'code' => 'AUX-ADM',   'level' => 11, 'dept' => 'SEC-GERAL'],
            ['name' => 'Rececionista',                                 'code' => 'RECEP',     'level' => 12, 'dept' => 'SEC-GERAL'],
            ['name' => 'Contínuo',                                     'code' => 'CONTIN',    'level' => 12, 'dept' => 'SEC-GERAL'],
            ['name' => 'Operador de Informática',                      'code' => 'OP-INFO',   'level' => 13, 'dept' => 'SEC-GERAL'],
            ['name' => 'Técnico de Recursos Humanos',                  'code' => 'TEC-RH',    'level' => 13, 'dept' => 'GAB-RH'],
            ['name' => 'Técnico de Finanças e Contabilidade',          'code' => 'TEC-FIN',   'level' => 13, 'dept' => 'SEC-GERAL'],
            ['name' => 'Técnico Jurídico',                             'code' => 'TEC-JUR',   'level' => 13, 'dept' => 'GAB-JUR'],
            ['name' => 'Técnico de Planeamento',                       'code' => 'TEC-PLAN',  'level' => 13, 'dept' => 'SEC-GERAL'],
            ['name' => 'Técnico de Informática',                       'code' => 'TEC-INFO',  'level' => 13, 'dept' => 'SEC-GERAL'],
            ['name' => 'Técnico de Património',                        'code' => 'TEC-PATR',  'level' => 13, 'dept' => 'SEC-GERAL'],
            ['name' => 'Técnico de Protocolo',                         'code' => 'TEC-PROT',  'level' => 13, 'dept' => 'GAB-GOV'],
            ['name' => 'Técnico de Comunicação Institucional',         'code' => 'TEC-COM',   'level' => 13, 'dept' => 'GAB-COM'],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['code' => $position['code']],
                [
                    'name' => $position['name'],
                    'department_id' => $departments[$position['dept']],
                    'level' => $position['level'],
                    'description' => "Cargo de {$position['name']}",
                    'is_active' => true,
                    'base_salary' => 0,
                ]
            );

            $this->command->info("Cargo '{$position['name']}' criado/actualizado.");
        }
    }
}
