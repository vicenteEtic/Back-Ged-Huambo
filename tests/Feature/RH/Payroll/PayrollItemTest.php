<?php

namespace Tests\Feature\RH\Payroll;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Payroll\PayrollItem;

class PayrollItemTest extends RhTestCase
{
    protected string $model = PayrollItem::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('payroll_item.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('payroll_item.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('payroll_item.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('payroll_item.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('payroll_item.destroy', $item->id));
        $response->assertStatus(204);
    }
}
