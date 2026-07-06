<?php

namespace Tests\Feature\RH\EmployeeDocument;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\EmployeeDocument\EmployeeDocument;
use App\Models\RH\Employee\Employee;

class EmployeeDocumentTest extends RhTestCase
{
    protected string $model = EmployeeDocument::class;

    public function test_can_list(): void
    {
        $employee = Employee::factory()->create();
        $response = $this->getJsonAuth(route('employee_document.index', ['employee_id' => $employee->id]));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $employee = Employee::factory()->create();
        $data = $this->model::factory()->make(['employee_id' => $employee->id])->toArray();
        $response = $this->postJsonAuth(route('employee_document.store', ['employee_id' => $employee->id]), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('employee_document.show', ['employee_id' => $item->employee_id, 'id' => $item->id]));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('employee_document.update', ['employee_id' => $item->employee_id, 'id' => $item->id]), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('employee_document.destroy', ['employee_id' => $item->employee_id, 'id' => $item->id]));
        $response->assertStatus(204);
    }
}
