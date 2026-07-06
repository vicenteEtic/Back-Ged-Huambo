<?php

namespace Tests\Feature\RH\Archive;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Archive\ArchiveCategory;

class ArchiveCategoryTest extends RhTestCase
{
    protected string $model = ArchiveCategory::class;
    protected string $routeUri = '/api/rh/archive/categories';

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth($this->routeUri);
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth($this->routeUri, $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth($this->routeUri . '/' . $item->id);
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth($this->routeUri . '/' . $item->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth($this->routeUri . '/' . $item->id);
        $response->assertStatus(204);
    }
}
