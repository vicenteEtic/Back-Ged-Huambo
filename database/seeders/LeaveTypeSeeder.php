<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('leave_types')->insert([
            [
                'name' => 'Férias Anuais',
                'code' => 'ANNUAL',
                'description' => 'Férias anuais remuneradas.',
                'default_days' => 22,
                'allows_carryover' => true,
                'max_carryover_days' => 10,
                'requires_attachment' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Licença Médica',
                'code' => 'SICK',
                'description' => 'Licença por motivo de doença.',
                'default_days' => 30,
                'allows_carryover' => false,
                'max_carryover_days' => 0,
                'requires_attachment' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Licença de Maternidade',
                'code' => 'MATERNITY',
                'description' => 'Licença de maternidade.',
                'default_days' => 90,
                'allows_carryover' => false,
                'max_carryover_days' => 0,
                'requires_attachment' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Licença de Paternidade',
                'code' => 'PATERNITY',
                'description' => 'Licença de paternidade.',
                'default_days' => 7,
                'allows_carryover' => false,
                'max_carryover_days' => 0,
                'requires_attachment' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Licença por Luto',
                'code' => 'BEREAVEMENT',
                'description' => 'Licença por falecimento de familiar.',
                'default_days' => 5,
                'allows_carryover' => false,
                'max_carryover_days' => 0,
                'requires_attachment' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Licença de Casamento',
                'code' => 'MARRIAGE',
                'description' => 'Licença por casamento.',
                'default_days' => 5,
                'allows_carryover' => false,
                'max_carryover_days' => 0,
                'requires_attachment' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Licença sem Remuneração',
                'code' => 'UNPAID',
                'description' => 'Licença sem vencimento.',
                'default_days' => 0,
                'allows_carryover' => false,
                'max_carryover_days' => 0,
                'requires_attachment' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}