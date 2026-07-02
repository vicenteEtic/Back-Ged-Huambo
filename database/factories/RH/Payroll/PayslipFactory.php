<?php

namespace Database\Factories\RH\Payroll;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Payroll\PayrollPeriod;
use App\Models\RH\Payroll\Payslip;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    public function definition(): array
    {
        $base = fake()->randomFloat(2, 100000, 500000);
        $transport = fake()->randomFloat(2, 5000, 30000);
        $meal = fake()->randomFloat(2, 5000, 20000);
        $overtime = fake()->randomFloat(2, 0, 50000);
        $gross = $base + $transport + $meal + $overtime;
        $inss = $gross * 0.03;
        $irt = $gross * 0.11;
        $totalDed = $inss + $irt;
        $net = $gross - $totalDed;

        return [
            'employee_id' => Employee::factory(),
            'payroll_period_id' => PayrollPeriod::factory(),
            'payslip_number' => 'PS-' . fake()->unique()->numerify('########'),
            'base_salary' => $base,
            'transport_allowance' => $transport,
            'meal_allowance' => $meal,
            'overtime' => $overtime,
            'other_earnings' => 0,
            'gross_pay' => $gross,
            'inss_deduction' => $inss,
            'irt_deduction' => $irt,
            'other_deductions' => 0,
            'total_deductions' => $totalDed,
            'net_pay' => $net,
            'status' => 'generated',
            'generated_at' => now(),
        ];
    }
}
