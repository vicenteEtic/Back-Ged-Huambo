<?php

namespace Database\Seeders;

use App\Models\RH\Leave\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Férias Anuais',                 'code' => 'ANNUAL',     'default_days' => 22, 'allows_carryover' => true,  'max_carryover_days' => 10, 'requires_attachment' => false, 'description' => 'Férias anuais remuneradas.'],
            ['name' => 'Licença Médica',                'code' => 'SICK',       'default_days' => 30, 'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'description' => 'Licença por motivo de doença.'],
            ['name' => 'Licença de Maternidade',        'code' => 'MATERNITY',  'default_days' => 90, 'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'description' => 'Licença de maternidade.'],
            ['name' => 'Licença de Paternidade',        'code' => 'PATERNITY',  'default_days' => 7,  'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => false, 'description' => 'Licença de paternidade.'],
            ['name' => 'Licença por Luto',              'code' => 'BEREAVEMENT','default_days' => 5,  'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'description' => 'Licença por falecimento de familiar.'],
            ['name' => 'Licença de Casamento',          'code' => 'MARRIAGE',   'default_days' => 5,  'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'description' => 'Licença por casamento.'],
            ['name' => 'Licença sem Remuneração',       'code' => 'UNPAID',     'default_days' => 0,  'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'description' => 'Licença sem vencimento.'],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
