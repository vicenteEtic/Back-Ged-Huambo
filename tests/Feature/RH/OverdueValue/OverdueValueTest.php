<?php

namespace Tests\Feature\RH\OverdueValue;

use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\OverdueValue\OverdueValue;
use App\Models\RH\Position\Position;
use Tests\Feature\RH\RhTestCase;

class OverdueValueTest extends RhTestCase
{
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $this->employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_list()
    {
        OverdueValue::factory()->count(3)->create(['employee_id' => $this->employee->id]);

        $response = $this->getJsonAuth('/api/rh/overdue-values');
        $response->assertStatus(200);
    }

    public function test_can_create()
    {
        $data = OverdueValue::factory()->make([
            'employee_id' => $this->employee->id,
            'amount' => 1500.25,
        ])->toArray();
        unset($data['created_at'], $data['updated_at']);

        $response = $this->postJsonAuth('/api/rh/overdue-values', $data);
        $response->assertStatus(201)
            ->assertJsonPath('employee_id', $this->employee->id)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('remaining_amount', 1500.25);
    }

    public function test_can_show()
    {
        $overdue = OverdueValue::factory()->create(['employee_id' => $this->employee->id]);

        $response = $this->getJsonAuth('/api/rh/overdue-values/'.$overdue->id);
        $response->assertStatus(200);
    }

    public function test_can_update()
    {
        $overdue = OverdueValue::factory()->create(['employee_id' => $this->employee->id]);

        $response = $this->putJsonAuth('/api/rh/overdue-values/'.$overdue->id, [
            'description' => 'Valor corrigido',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('description', 'Valor corrigido');
    }

    public function test_can_destroy()
    {
        $overdue = OverdueValue::factory()->create(['employee_id' => $this->employee->id]);

        $response = $this->deleteJsonAuth('/api/rh/overdue-values/'.$overdue->id);
        $response->assertStatus(204);
    }

    public function test_validates_amount()
    {
        $response = $this->postJsonAuth('/api/rh/overdue-values', [
            'employee_id' => $this->employee->id,
            'type' => 'receivable',
            'description' => 'Valor em atraso',
            'amount' => 0,
        ]);

        $response->assertStatus(422);
    }

    public function test_full_payment_sets_status_settled()
    {
        $data = OverdueValue::factory()->make([
            'employee_id' => $this->employee->id,
            'amount' => 1000,
        ])->toArray();
        unset($data['created_at'], $data['updated_at']);

        $data['paid_amount'] = 1000;

        $response = $this->postJsonAuth('/api/rh/overdue-values', $data);
        $response->assertStatus(201)
            ->assertJsonPath('status', 'settled')
            ->assertJsonPath('remaining_amount', 0);
    }

    public function test_partial_payment_sets_partially_paid_status()
    {
        $data = OverdueValue::factory()->make([
            'employee_id' => $this->employee->id,
            'amount' => 1000,
        ])->toArray();
        unset($data['created_at'], $data['updated_at']);

        $data['paid_amount'] = 300;

        $response = $this->postJsonAuth('/api/rh/overdue-values', $data);
        $response->assertStatus(201)
            ->assertJsonPath('status', 'partially_paid')
            ->assertJsonPath('remaining_amount', 700);
    }

    public function test_summary_groups_by_employee()
    {
        OverdueValue::factory()->create([
            'employee_id' => $this->employee->id,
            'type' => 'receivable',
            'amount' => 1000,
            'status' => 'pending',
        ]);

        OverdueValue::factory()->create([
            'employee_id' => $this->employee->id,
            'type' => 'payable',
            'amount' => 500,
            'status' => 'pending',
        ]);

        $response = $this->getJsonAuth('/api/rh/overdue-values/summary');
        $response->assertStatus(200)
            ->assertJsonPath('totals.receivable', 1000)
            ->assertJsonPath('totals.payable', 500)
            ->assertJsonPath('totals.count', 2)
            ->assertJsonPath('employees.0.full_name', $this->employee->full_name)
            ->assertJsonPath('employees.0.receivable_total', 1000)
            ->assertJsonPath('employees.0.payable_total', 500);
    }

    public function test_summary_excludes_settled_values()
    {
        OverdueValue::factory()->create([
            'employee_id' => $this->employee->id,
            'type' => 'receivable',
            'amount' => 1000,
            'status' => 'settled',
            'paid_amount' => 1000,
        ]);

        $response = $this->getJsonAuth('/api/rh/overdue-values/summary');
        $response->assertStatus(200)
            ->assertJsonPath('totals.receivable', 0)
            ->assertJsonPath('totals.count', 0);
    }
}
