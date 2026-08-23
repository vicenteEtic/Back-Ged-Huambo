<?php

namespace Tests\Feature\RH\Payroll;

use App\Models\RH\Category\Category;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Payroll\PayrollItem;
use App\Models\RH\Payroll\PayrollPeriod;
use Tests\Feature\RH\RhTestCase;

class PayrollItemSalaryFromCategoryTest extends RhTestCase
{
    public function test_base_salary_is_prefilled_from_category_when_not_sent(): void
    {
        $category = Category::factory()->create(['base_salary' => 250000]);
        $employee = Employee::factory()->create([
            'category' => $category->id,
            'base_salary' => 100000,
        ]);
        $period = PayrollPeriod::factory()->create();

        $response = $this->postJsonAuth(route('payroll_item.store'), [
            'payroll_period_id' => $period->id,
            'items' => [
                ['employee_id' => $employee->id],
            ],
        ]);

        $response->assertStatus(201);

        $item = PayrollItem::where('employee_id', $employee->id)
            ->where('payroll_period_id', $period->id)
            ->first();

        $this->assertNotNull($item);
        $this->assertEquals(250000, (float) $item->base_salary);
        $this->assertEquals(7500, round((float) $item->inss_deduction, 2));
    }

    public function test_sent_salary_takes_priority_over_category(): void
    {
        $category = Category::factory()->create(['base_salary' => 250000]);
        $employee = Employee::factory()->create([
            'category' => $category->id,
            'base_salary' => 100000,
        ]);
        $period = PayrollPeriod::factory()->create();

        $response = $this->postJsonAuth(route('payroll_item.store'), [
            'payroll_period_id' => $period->id,
            'items' => [
                ['employee_id' => $employee->id, 'base_salary' => 300000],
            ],
        ]);

        $response->assertStatus(201);

        $item = PayrollItem::where('employee_id', $employee->id)
            ->where('payroll_period_id', $period->id)
            ->first();

        $this->assertEquals(300000, (float) $item->base_salary);
    }

    public function test_falls_back_to_employee_salary_without_category(): void
    {
        $employee = Employee::factory()->create([
            'category' => null,
            'base_salary' => 180000,
        ]);
        $period = PayrollPeriod::factory()->create();

        $response = $this->postJsonAuth(route('payroll_item.store'), [
            'payroll_period_id' => $period->id,
            'items' => [
                ['employee_id' => $employee->id],
            ],
        ]);

        $response->assertStatus(201);

        $item = PayrollItem::where('employee_id', $employee->id)
            ->where('payroll_period_id', $period->id)
            ->first();

        $this->assertEquals(180000, (float) $item->base_salary);
    }
}
