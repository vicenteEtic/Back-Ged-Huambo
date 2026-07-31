<?php

namespace App\Helpers;

use App\Models\RH\Payroll\IrtBracket;
use Illuminate\Support\Facades\Cache;

class PayrollCalculator
{
    private const INSS_RATE = 0.03;

    private const FALLBACK_IRT_TABLE = [
        ['bracket' => 1,  'min_salary' => 0,        'max_salary' => 150000,    'fixed_amount' => 0,       'rate' => 0,      'excess_over' => 0,        'is_exempt' => true],
        ['bracket' => 2,  'min_salary' => 150001,   'max_salary' => 200000,    'fixed_amount' => 12500,   'rate' => 0.16,   'excess_over' => 150000,   'is_exempt' => false],
        ['bracket' => 3,  'min_salary' => 200001,   'max_salary' => 300000,    'fixed_amount' => 31250,   'rate' => 0.18,   'excess_over' => 200000,   'is_exempt' => false],
        ['bracket' => 4,  'min_salary' => 300001,   'max_salary' => 500000,    'fixed_amount' => 49250,   'rate' => 0.19,   'excess_over' => 300000,   'is_exempt' => false],
        ['bracket' => 5,  'min_salary' => 500001,   'max_salary' => 1000000,   'fixed_amount' => 87250,   'rate' => 0.20,   'excess_over' => 500000,   'is_exempt' => false],
        ['bracket' => 6,  'min_salary' => 1000001,  'max_salary' => 1500000,   'fixed_amount' => 187250,  'rate' => 0.21,   'excess_over' => 1000000,  'is_exempt' => false],
        ['bracket' => 7,  'min_salary' => 1500001,  'max_salary' => 2000000,   'fixed_amount' => 292250,  'rate' => 0.22,   'excess_over' => 1500000,  'is_exempt' => false],
        ['bracket' => 8,  'min_salary' => 2000001,  'max_salary' => 2500000,   'fixed_amount' => 402250,  'rate' => 0.23,   'excess_over' => 2000000,  'is_exempt' => false],
        ['bracket' => 9,  'min_salary' => 2500001,  'max_salary' => 5000000,   'fixed_amount' => 517250,  'rate' => 0.24,   'excess_over' => 2500000,  'is_exempt' => false],
        ['bracket' => 10, 'min_salary' => 5000001,  'max_salary' => 10000000,  'fixed_amount' => 1117250, 'rate' => 0.245,  'excess_over' => 5000000,  'is_exempt' => false],
        ['bracket' => 11, 'min_salary' => 10000001, 'max_salary' => 999999999, 'fixed_amount' => 2342250, 'rate' => 0.25,   'excess_over' => 10000000, 'is_exempt' => false],
    ];

    private static function getIrtBrackets(): array
    {
        try {
            $brackets = IrtBracket::active()->orderBy('bracket')->get()->toArray();
            if (!empty($brackets)) {
                return $brackets;
            }
        } catch (\Throwable $e) {
            // tabela ainda não existe, usa fallback
        }

        return self::FALLBACK_IRT_TABLE;
    }

    public static function calculateINSS(float $baseSalary): float
    {
        return round($baseSalary * self::INSS_RATE, 2);
    }

    public static function calculateIRT(float $materiaColetavel): float
    {
        $brackets = self::getIrtBrackets();

        foreach ($brackets as $bracket) {
            if ((bool) ($bracket['is_exempt'] ?? false)) {
                continue;
            }

            $min = (float) $bracket['min_salary'];
            $max = (float) $bracket['max_salary'];

            if ($materiaColetavel >= $min && $materiaColetavel <= $max) {
                $fixed = (float) $bracket['fixed_amount'];
                $rate = (float) $bracket['rate'];
                $excess = (float) $bracket['excess_over'];

                return round($fixed + (($materiaColetavel - $excess) * $rate), 2);
            }
        }

        return 0;
    }

    public static function calculate(array $data): array
    {
        $baseSalary = (float) ($data['base_salary'] ?? 0);
        $transport = (float) ($data['transport_allowance'] ?? 0);
        $meal = (float) ($data['meal_allowance'] ?? 0);
        $overtime = (float) ($data['overtime'] ?? 0);
        $otherEarnings = (float) ($data['other_earnings'] ?? 0);
        $otherDeductions = (float) ($data['other_deductions'] ?? 0);

        $grossPay = $baseSalary + $transport + $meal + $overtime + $otherEarnings;

        $inss = self::calculateINSS($baseSalary);

        $materiaColetavel = $grossPay - $inss;
        $irt = self::calculateIRT($materiaColetavel);

        $totalDeductions = $inss + $irt + $otherDeductions;
        $netPay = $grossPay - $totalDeductions;

        return array_merge($data, [
            'gross_pay' => round($grossPay, 2),
            'inss_deduction' => $inss,
            'irt_deduction' => $irt,
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => round($netPay, 2),
        ]);
    }
}
