<?php

namespace Tests\Feature\RH\Benefit;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Benefit\EmployeeBenefit;

class EmployeeBenefitTest extends RhTestCase
{
    protected string $model = EmployeeBenefit::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('employee_benefit.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('employee_benefit.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('employee_benefit.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('employee_benefit.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('employee_benefit.destroy', $item->id));
        $response->assertStatus(204);
    }
}
