<?php

namespace Tests\Feature\RH\Position;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Position\Position;

class PositionTest extends RhTestCase
{
    protected string $model = Position::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('position.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('position.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('position.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('position.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('position.destroy', $item->id));
        $response->assertStatus(204);
    }

    public function test_can_create_cargo_type(): void
    {
        $data = $this->model::factory()->make(['type' => 'cargo'])->toArray();
        $response = $this->postJsonAuth(route('position.store'), $data);
        $response->assertStatus(201)->assertJsonPath('type', 'cargo');
    }

    public function test_ignores_fields_other_than_name(): void
    {
        $response = $this->postJsonAuth(route('position.store'), [
            'name' => 'Cargo Teste',
            'base_salary' => 999999,
            'department_id' => 1,
            'type' => 'funcao_qualquer',
            'requirements' => 'x',
        ]);

        $response->assertStatus(201);
        $item = $this->model::find($response->json('id') ?? $response->json('0.id'));
        $this->assertSame('cargo', $item->type);
        $this->assertEquals(0, $item->base_salary);
        $this->assertNull($item->department_id);
        $this->assertNotNull($item->code);
    }
}
