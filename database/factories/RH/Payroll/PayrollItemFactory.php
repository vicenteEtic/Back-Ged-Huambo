<?php

namespace Database\Factories\RH\Payroll;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Payroll\PayrollItem;
use App\Models\RH\Payroll\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollItemFactory extends Factory
{
    protected $model = PayrollItem::class;

    public function definition(): array
    {
        return [
            'payroll_period_id' => PayrollPeriod::factory(),
            'employee_id' => Employee::factory(),
            'base_salary' => fake()->randomFloat(2, 100000, 500000),
            'transport_allowance' => fake()->randomFloat(2, 5000, 30000),
            'meal_allowance' => fake()->randomFloat(2, 5000, 20000),
            'overtime' => fake()->randomFloat(2, 0, 50000),
            'bonuses' => fake()->randomFloat(2, 0, 50000),
            'inss' => fake()->randomFloat(2, 5000, 30000),
            'irt' => fake()->randomFloat(2, 5000, 50000),
            'other_deductions' => fake()->randomFloat(2, 0, 10000),
            'gross_pay' => 0,
            'net_pay' => 0,
            'status' => 'computed',
        ];
    }
}
