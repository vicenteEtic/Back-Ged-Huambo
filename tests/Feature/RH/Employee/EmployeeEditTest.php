<?php

namespace Tests\Feature\RH\Employee;

use App\Models\RH\Category\Category;
use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Position\Position;
use App\Models\User;
use Tests\Feature\RH\RhTestCase;

class EmployeeEditTest extends RhTestCase
{
    protected function createEmployee(array $attributes = []): Employee
    {
        $dept = Department::factory()->create();
        $pos = Position::factory()->create(['department_id' => $dept->id]);
        $linkedUser = User::factory()->create();

        return Employee::factory()->create(array_merge([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'user_id' => $linkedUser->id,
            'employee_number' => 'AGT-001',
        ], $attributes));
    }

    public function test_can_edit_employee_number()
    {
        $employee = $this->createEmployee();

        $this->putJsonAuth("/api/rh/employees/{$employee->id}", [
            'employee_number' => 'AGT-999',
        ])->assertStatus(200);

        $this->assertSame('AGT-999', $employee->fresh()->employee_number);
    }

    public function test_cannot_edit_employee_number_to_a_duplicated_value()
    {
        $employee = $this->createEmployee();
        Employee::factory()->create(['employee_number' => 'AGT-DUP']);

        $this->putJsonAuth("/api/rh/employees/{$employee->id}", [
            'employee_number' => 'AGT-DUP',
        ])->assertStatus(422)->assertJsonValidationErrors('employee_number');
    }

    public function test_can_disassociate_user_from_employee()
    {
        $employee = $this->createEmployee();
        $this->assertNotNull($employee->user_id);

        $this->putJsonAuth("/api/rh/employees/{$employee->id}", [
            'user_id' => null,
        ])->assertStatus(200);

        $this->assertNull($employee->fresh()->user_id);
    }

    public function test_can_disassociate_position_from_employee()
    {
        $employee = $this->createEmployee();
        $this->assertNotNull($employee->position_id);

        $this->putJsonAuth("/api/rh/employees/{$employee->id}", [
            'position_id' => null,
        ])->assertStatus(200);

        $this->assertNull($employee->fresh()->position_id);
    }

    public function test_can_disassociate_category_from_employee()
    {
        $employee = $this->createEmployee(['category' => Category::factory()]);
        $this->assertNotNull($employee->category);

        $this->putJsonAuth("/api/rh/employees/{$employee->id}", [
            'category' => null,
        ])->assertStatus(200);

        $this->assertNull($employee->fresh()->category);
    }

    public function test_cannot_associate_nonexistent_user()
    {
        $employee = $this->createEmployee();

        $this->putJsonAuth("/api/rh/employees/{$employee->id}", [
            'user_id' => 99999,
        ])->assertStatus(422)->assertJsonValidationErrors('user_id');
    }
}
