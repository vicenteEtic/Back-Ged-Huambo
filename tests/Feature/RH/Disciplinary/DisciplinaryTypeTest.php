<?php

namespace Tests\Feature\RH\Disciplinary;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Disciplinary\DisciplinaryType;

class DisciplinaryTypeTest extends RhTestCase
{
    protected string $model = DisciplinaryType::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('disciplinary_type.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('disciplinary_type.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('disciplinary_type.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('disciplinary_type.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('disciplinary_type.destroy', $item->id));
        $response->assertStatus(204);
    }
}
