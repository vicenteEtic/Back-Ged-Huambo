<?php

namespace Tests\Feature\RH\Employee;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class EmployeeTest extends RhTestCase
{
    public function test_can_list()
    {
        $response = $this->getJsonAuth('/api/rh/employees');
        $response->assertStatus(200);
    }

    public function test_can_create()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $user = User::factory()->create();

        $data = Employee::factory()->make([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $user->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/employees', $data);
        $response->assertStatus(201);
    }

    public function test_can_show()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/employees/' . $employee->id);
        $response->assertStatus(200);
    }

    public function test_can_update()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $data = Employee::factory()->make()->toArray();
        $response = $this->putJsonAuth('/api/rh/employees/' . $employee->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/employees/' . $employee->id);
        $response->assertStatus(204);
    }
}
