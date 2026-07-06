<?php

namespace Tests\Feature\RH\Performance;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Performance\PerformanceGoal;

class PerformanceGoalTest extends RhTestCase
{
    protected string $model = PerformanceGoal::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('performance_goal.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('performance_goal.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('performance_goal.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('performance_goal.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('performance_goal.destroy', $item->id));
        $response->assertStatus(204);
    }
}
