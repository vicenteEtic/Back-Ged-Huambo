<?php

namespace Tests\Feature\RH\Attendance;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Attendance\Shift;

class ShiftTest extends RhTestCase
{
    protected string $model = Shift::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('shift.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('shift.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('shift.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('shift.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('shift.destroy', $item->id));
        $response->assertStatus(204);
    }
}
