<?php

namespace Tests\Feature\RH\Payroll;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Payroll\PayrollPeriod;

class PayrollPeriodTest extends RhTestCase
{
    protected string $model = PayrollPeriod::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('payroll_period.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('payroll_period.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('payroll_period.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('payroll_period.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('payroll_period.destroy', $item->id));
        $response->assertStatus(204);
    }
}
