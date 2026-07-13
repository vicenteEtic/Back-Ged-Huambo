<?php

namespace Database\Seeders;

use App\Models\RH\Area\Area;
use App\Models\RH\Department\Department;
use Illuminate\Database\Seeder;

class AreaSeed extends Seeder
{
    public function run(): void
    {
        $departments = Department::pluck('id', 'code');

        $areas = [
            // === GAB-RH (Gabinete de Recursos Humanos) ===
            ['department' => 'GAB-RH', 'name' => 'Administração de Pessoal',        'code' => 'GAB-RH-AP',  'description' => 'Gestão de processos de pessoal, admissões, desligamentos'],
            ['department' => 'GAB-RH', 'name' => 'Remunerações e Benefícios',        'code' => 'GAB-RH-RB',  'description' => 'Processamento salarial, subsídios, benefícios sociais'],
            ['department' => 'GAB-RH', 'name' => 'Formação e Desenvolvimento',       'code' => 'GAB-RH-FD',  'description' => 'Planos de formação, capacitação, certificações'],
            ['department' => 'GAB-RH', 'name' => 'Assiduidade e Controlo Horário',   'code' => 'GAB-RH-ACH', 'description' => 'Registo de ponto, férias, licenças'],

            // === GAB-JUR (Gabinete Jurídico) ===
            ['department' => 'GAB-JUR', 'name' => 'Assessoria Jurídica',             'code' => 'GAB-JUR-AJ', 'description' => 'Consultoria jurídica, pareceres, contratos'],
            ['department' => 'GAB-JUR', 'name' => 'Contencioso',                     'code' => 'GAB-JUR-CONT', 'description' => 'Processos judiciais, arbitrais, mediação'],

            // === GAB-COM (Gabinete de Comunicação) ===
            ['department' => 'GAB-COM', 'name' => 'Comunicação Institucional',       'code' => 'GAB-COM-CI', 'description' => 'Relações públicas, imagem institucional'],
            ['department' => 'GAB-COM', 'name' => 'Imprensa e Media',                'code' => 'GAB-COM-IM', 'description' => 'Assessoria de imprensa, contacts com media'],

            // === GAB-GOV (Gabinete do Governador) ===
            ['department' => 'GAB-GOV', 'name' => 'Protocolo',                       'code' => 'GAB-GOV-PROT', 'description' => 'Eventos, cerimónias, visitas oficiais'],
            ['department' => 'GAB-GOV', 'name' => 'Assessoria Política',             'code' => 'GAB-GOV-AP', 'description' => 'Apoio político, agenda do governador'],

            // === SEC-GERAL (Secretaria-Geral) ===
            ['department' => 'SEC-GERAL', 'name' => 'Gabinete do Secretário-Geral',  'code' => 'SEC-GAB',    'description' => 'Apoio directo ao Secretário-Geral'],
            ['department' => 'SEC-GERAL', 'name' => 'Planeamento e Acção Governativa','code' => 'SEC-PLAN',   'description' => 'Planos estratégicos, projectos, execução orçamental'],
            ['department' => 'SEC-GERAL', 'name' => 'Finanças e Contabilidade',      'code' => 'SEC-FIN',    'description' => 'Gestão financeira, contabilidade, orçamento'],
            ['department' => 'SEC-GERAL', 'name' => 'Informática e Telecomunicações','code' => 'SEC-INFO',   'description' => 'TIC, redes, sistemas de informação'],
            ['department' => 'SEC-GERAL', 'name' => 'Património e Bens',             'code' => 'SEC-PATR',   'description' => 'Gestão de património, inventário, manutenção'],
        ];

        foreach ($areas as $area) {
            if (!isset($departments[$area['department']])) {
                $this->command->warn("Departamento '{$area['department']}' não encontrado. Áreas ignoradas.");
                continue;
            }

            Area::updateOrCreate(
                ['code' => $area['code']],
                [
                    'department_id' => $departments[$area['department']],
                    'name' => $area['name'],
                    'description' => $area['description'],
                    'is_active' => true,
                ]
            );

            $this->command->info("Área '{$area['name']}' ({$area['code']}) criada/actualizada.");
        }
    }
}
