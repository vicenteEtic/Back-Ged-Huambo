<?php

namespace Tests\Feature\RH\Career;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Career\ProgressionRule;

class ProgressionRuleTest extends RhTestCase
{
    protected string $model = ProgressionRule::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('progression_rule.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('progression_rule.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('progression_rule.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('progression_rule.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('progression_rule.destroy', $item->id));
        $response->assertStatus(204);
    }
}
