<?php

namespace Tests\Feature\RH\Category;

use App\Models\RH\Category\Category;
use Tests\Feature\RH\RhTestCase;

class CategoryTest extends RhTestCase
{
    protected string $model = Category::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('category.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('category.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('category.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('category.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('category.destroy', $item->id));
        $response->assertStatus(204);
    }

    public function test_employee_category_must_exist(): void
    {
        $employee = \App\Models\RH\Employee\Employee::factory()->create();

        $response = $this->putJsonAuth("/api/rh/employees/{$employee->id}", [
            'category' => 999999,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('category');
    }
}
