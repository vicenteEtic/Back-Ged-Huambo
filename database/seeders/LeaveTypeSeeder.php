<?php

namespace Database\Seeders;

use App\Models\RH\Leave\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Férias Anuais',                 'code' => 'ANNUAL',     'default_days' => 22, 'service_years_based' => true, 'allows_carryover' => true,  'max_carryover_days' => 10, 'requires_attachment' => false, 'allows_extension' => false, 'extension_days' => null, 'max_extensions' => null, 'description' => 'Férias anuais remuneradas. O número de dias é calculado com base no tempo de serviço do funcionário.'],
            ['name' => 'Licença Médica',                'code' => 'SICK',       'default_days' => 30, 'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'allows_extension' => true, 'extension_days' => 30, 'max_extensions' => 1, 'description' => 'Licença por motivo de doença. Prorrogável uma única vez pelo mesmo período mediante relatório médico.'],
            ['name' => 'Licença de Maternidade',        'code' => 'MATERNITY',  'default_days' => 90, 'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'allows_extension' => true, 'extension_days' => 30, 'max_extensions' => 1, 'description' => 'Licença de maternidade. Prorrogável por recomendação clínica.'],
            ['name' => 'Licença de Paternidade',        'code' => 'PATERNITY',  'default_days' => 7,  'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => false, 'allows_extension' => false, 'extension_days' => null, 'max_extensions' => null, 'description' => 'Licença de paternidade.'],
            ['name' => 'Licença por Luto',              'code' => 'BEREAVEMENT','default_days' => 5,  'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'allows_extension' => false, 'extension_days' => null, 'max_extensions' => null, 'description' => 'Licença por falecimento de familiar.'],
            ['name' => 'Licença de Casamento',          'code' => 'MARRIAGE',   'default_days' => 5,  'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'allows_extension' => false, 'extension_days' => null, 'max_extensions' => null, 'description' => 'Licença por casamento.'],
            ['name' => 'Licença sem Remuneração',       'code' => 'UNPAID',     'default_days' => 0,  'allows_carryover' => false, 'max_carryover_days' => 0,  'requires_attachment' => true,  'allows_extension' => false, 'extension_days' => null, 'max_extensions' => null, 'description' => 'Licença sem vencimento. Pode ser concedida por tempo indeterminado.'],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}