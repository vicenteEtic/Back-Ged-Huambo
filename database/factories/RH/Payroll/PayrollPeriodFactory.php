<?php

namespace Database\Factories\RH\Payroll;

use App\Models\RH\Payroll\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $year = now()->year;
        $month = fake()->numberBetween(1, 12);

        return [
            'name' => "Processamento {$month}/{$year}",
            'year' => $year,
            'month' => $month,
            'start_date' => "{$year}-{$month}-01",
            'end_date' => "{$year}-{$month}-" . cal_days_in_month(CAL_GREGORIAN, $month, $year),
            'status' => 'open',
        ];
    }
}
