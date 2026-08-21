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

    private function employeePayload(array $overrides = []): array
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        return array_merge([
            'employee_number' => 'AGT-' . fake()->unique()->numerify('#####'),
            'department_id' => $department->id,
            'position_id' => $position->id,
            'full_name' => 'Teste Admissão',
        ], $overrides);
    }

    public function test_admission_allows_employee_aged_at_least_18(): void
    {
        $response = $this->postJsonAuth('/api/rh/employees', $this->employeePayload([
            'date_of_birth' => '2000-05-10',
            'hire_date' => '2018-05-10',
        ]));

        $response->assertStatus(201);
    }

    public function test_admission_rejects_employee_under_18(): void
    {
        $response = $this->postJsonAuth('/api/rh/employees', $this->employeePayload([
            'date_of_birth' => '2010-01-01',
            'hire_date' => '2025-01-01',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('hire_date');
    }

    public function test_admission_rejects_birth_date_in_the_future(): void
    {
        $response = $this->postJsonAuth('/api/rh/employees', $this->employeePayload([
            'date_of_birth' => now()->addYear()->toDateString(),
            'hire_date' => now()->toDateString(),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('date_of_birth');
    }

    public function test_admission_rejects_hire_date_before_birth_date(): void
    {
        $response = $this->postJsonAuth('/api/rh/employees', $this->employeePayload([
            'date_of_birth' => '1990-01-01',
            'hire_date' => '1980-01-01',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('hire_date');
    }

    public function test_effective_date_requires_employee_aged_at_least_18(): void
    {
        $response = $this->postJsonAuth('/api/rh/employees', $this->employeePayload([
            'date_of_birth' => '2005-10-19',
            'hire_date' => '2025-01-01',
            'effective_date' => '2021-01-27',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('effective_date');
    }

    public function test_effective_date_allows_employee_aged_at_least_18(): void
    {
        $response = $this->postJsonAuth('/api/rh/employees', $this->employeePayload([
            'date_of_birth' => '2005-10-19',
            'hire_date' => '2025-01-01',
            'effective_date' => '2024-10-19',
        ]));

        $response->assertStatus(201);
    }

    public function test_update_allows_changing_metadata_without_age_check(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $response = $this->putJsonAuth('/api/rh/employees/' . $employee->id, [
            'phone' => '999999999',
            'address' => 'Rua Nova',
        ]);

        $response->assertStatus(200);
    }
}
