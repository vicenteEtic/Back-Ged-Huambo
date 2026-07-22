<?php

namespace Database\Seeders;

use App\Models\RH\Payroll\IrtBracket;
use Illuminate\Database\Seeder;

class IrtBracketSeeder extends Seeder
{
    public function run(): void
    {
        $brackets = [
            ['bracket' => 1,  'min_salary' => 0,        'max_salary' => 150000,    'fixed_amount' => 0,       'rate' => 0,      'excess_over' => 0,        'is_exempt' => true,  'active' => true],
            ['bracket' => 2,  'min_salary' => 150001,   'max_salary' => 200000,    'fixed_amount' => 12500,   'rate' => 0.16,   'excess_over' => 150000,   'is_exempt' => false, 'active' => true],
            ['bracket' => 3,  'min_salary' => 200001,   'max_salary' => 300000,    'fixed_amount' => 31250,   'rate' => 0.18,   'excess_over' => 200000,   'is_exempt' => false, 'active' => true],
            ['bracket' => 4,  'min_salary' => 300001,   'max_salary' => 500000,    'fixed_amount' => 49250,   'rate' => 0.19,   'excess_over' => 300000,   'is_exempt' => false, 'active' => true],
            ['bracket' => 5,  'min_salary' => 500001,   'max_salary' => 1000000,   'fixed_amount' => 87250,   'rate' => 0.20,   'excess_over' => 500000,   'is_exempt' => false, 'active' => true],
            ['bracket' => 6,  'min_salary' => 1000001,  'max_salary' => 1500000,   'fixed_amount' => 187250,  'rate' => 0.21,   'excess_over' => 1000000,  'is_exempt' => false, 'active' => true],
            ['bracket' => 7,  'min_salary' => 1500001,  'max_salary' => 2000000,   'fixed_amount' => 292250,  'rate' => 0.22,   'excess_over' => 1500000,  'is_exempt' => false, 'active' => true],
            ['bracket' => 8,  'min_salary' => 2000001,  'max_salary' => 2500000,   'fixed_amount' => 402250,  'rate' => 0.23,   'excess_over' => 2000000,  'is_exempt' => false, 'active' => true],
            ['bracket' => 9,  'min_salary' => 2500001,  'max_salary' => 5000000,   'fixed_amount' => 517250,  'rate' => 0.24,   'excess_over' => 2500000,  'is_exempt' => false, 'active' => true],
            ['bracket' => 10, 'min_salary' => 5000001,  'max_salary' => 10000000,  'fixed_amount' => 1117250, 'rate' => 0.245,  'excess_over' => 5000000,  'is_exempt' => false, 'active' => true],
            ['bracket' => 11, 'min_salary' => 10000001, 'max_salary' => 999999999, 'fixed_amount' => 2342250, 'rate' => 0.25,   'excess_over' => 10000000, 'is_exempt' => false, 'active' => true],
        ];

        foreach ($brackets as $bracket) {
            IrtBracket::updateOrCreate(
                ['bracket' => $bracket['bracket']],
                $bracket
            );
        }
    }
}
