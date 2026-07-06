<?php

namespace Tests\Feature\RH\Recruitment;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Recruitment\Application;

class ApplicationTest extends RhTestCase
{
    protected string $model = Application::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('application.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('application.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('application.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('application.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('application.destroy', $item->id));
        $response->assertStatus(204);
    }
}
