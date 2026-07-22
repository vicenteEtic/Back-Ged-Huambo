<?php

namespace App\Helpers;

class PayrollCalculator
{
    private const INSS_RATE = 0.03;

    private const IRT_TABLE = [
        1 => ['min' => 0,        'max' => 150000,     'parcela_fixa' => 0,        'taxa' => 0,    'excesso' => 0],
        2 => ['min' => 150001,   'max' => 200000,     'parcela_fixa' => 12500,    'taxa' => 0.16, 'excesso' => 150000],
        3 => ['min' => 200001,   'max' => 300000,     'parcela_fixa' => 31250,    'taxa' => 0.18, 'excesso' => 200000],
        4 => ['min' => 300001,   'max' => 500000,     'parcela_fixa' => 49250,    'taxa' => 0.19, 'excesso' => 300000],
        5 => ['min' => 500001,   'max' => 1000000,    'parcela_fixa' => 87250,    'taxa' => 0.20, 'excesso' => 500000],
        6 => ['min' => 1000001,  'max' => 1500000,    'parcela_fixa' => 187250,   'taxa' => 0.21, 'excesso' => 1000000],
        7 => ['min' => 1500001,  'max' => 2000000,    'parcela_fixa' => 292250,   'taxa' => 0.22, 'excesso' => 1500000],
        8 => ['min' => 2000001,  'max' => 2500000,    'parcela_fixa' => 402250,   'taxa' => 0.23, 'excesso' => 2000000],
        9 => ['min' => 2500001,  'max' => 5000000,    'parcela_fixa' => 517250,   'taxa' => 0.24, 'excesso' => 2500000],
        10 => ['min' => 5000001, 'max' => 10000000,   'parcela_fixa' => 1117250,  'taxa' => 0.245,'excesso' => 5000000],
        11 => ['min' => 10000001,'max' => PHP_INT_MAX, 'parcela_fixa' => 2342250,  'taxa' => 0.25, 'excesso' => 10000000],
    ];

    public static function calculateINSS(float $baseSalary): float
    {
        return round($baseSalary * self::INSS_RATE, 2);
    }

    public static function calculateIRT(float $materiaColetavel): float
    {
        if ($materiaColetavel <= 150000) {
            return 0;
        }

        foreach (self::IRT_TABLE as $escalao) {
            if ($materiaColetavel >= $escalao['min'] && $materiaColetavel <= $escalao['max']) {
                return round($escalao['parcela_fixa'] + (($materiaColetavel - $escalao['excesso']) * $escalao['taxa']), 2);
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
