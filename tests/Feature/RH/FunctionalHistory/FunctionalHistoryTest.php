<?php

namespace Tests\Feature\RH\FunctionalHistory;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\FunctionalHistory\FunctionalHistory;

class FunctionalHistoryTest extends RhTestCase
{
    protected string $model = FunctionalHistory::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('functional_history.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('functional_history.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('functional_history.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('functional_history.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('functional_history.destroy', $item->id));
        $response->assertStatus(204);
    }
}
