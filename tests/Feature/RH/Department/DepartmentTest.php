<?php

namespace Tests\Feature\RH\Department;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Department\Department;

class DepartmentTest extends RhTestCase
{
    protected string $model = Department::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('department.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('department.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('department.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('department.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('department.destroy', $item->id));
        $response->assertStatus(204);
    }
}
