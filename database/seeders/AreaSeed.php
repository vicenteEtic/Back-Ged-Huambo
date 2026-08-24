<?php

namespace Database\Seeders;

use App\Models\RH\Area\Area;
use Illuminate\Database\Seeder;

class AreaSeed extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Secretaria-Geral',                                                    'code' => 'SEC-GERAL', 'type' => 'departamento'],
            ['name' => 'Gabinete Jurídico e de Intercâmbio',                                  'code' => 'GAB-JUR',   'type' => 'gabinete'],
            ['name' => 'Gabinete de Comunicação Social',                                      'code' => 'GAB-COM',   'type' => 'gabinete'],
            ['name' => 'Gabinete de Recursos Humanos',                                        'code' => 'GAB-RH',    'type' => 'gabinete'],
            ['name' => 'Gabinete do Governador',                                              'code' => 'GAB-GOV',   'type' => 'gabinete'],
            ['name' => 'Gabinete do Vice-Governador para o Sector Político, Social e Económico',          'code' => 'VICE-PSE',  'type' => 'vice_governador'],
            ['name' => 'Gabinete do Vice-Governador para os Serviços Técnicos e Infraestruturas',         'code' => 'VICE-STI',  'type' => 'vice_governador'],
        ];

        foreach ($areas as $area) {
            Area::updateOrCreate(
                ['code' => $area['code']],
                [
                    'name' => $area['name'],
                    'description' => ucfirst(str_replace('_', ' ', $area['type'])).' — '.$area['name'],
                    'is_active' => true,
                ]
            );

            $this->command->info("Área '{$area['name']}' ({$area['code']}) criada/actualizada.");
        }

        $removidas = Area::whereNotIn('code', array_column($areas, 'code'))->get();
        foreach ($removidas as $area) {
            $area->delete();
            $this->command->warn("Área '{$area->name}' ({$area->code}) removida — não pertence ao quadro oficial.");
        }
    }
}
