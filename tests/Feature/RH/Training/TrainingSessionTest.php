<?php

namespace Tests\Feature\RH\Training;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Training\TrainingSession;

class TrainingSessionTest extends RhTestCase
{
    protected string $model = TrainingSession::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('training_session.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('training_session.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('training_session.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('training_session.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('training_session.destroy', $item->id));
        $response->assertStatus(204);
    }
}
