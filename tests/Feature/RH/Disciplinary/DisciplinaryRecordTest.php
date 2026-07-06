<?php

namespace Tests\Feature\RH\Disciplinary;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Disciplinary\DisciplinaryRecord;

class DisciplinaryRecordTest extends RhTestCase
{
    protected string $model = DisciplinaryRecord::class;

    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('disciplinary_record.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $data = $this->model::factory()->make()->toArray();
        $response = $this->postJsonAuth(route('disciplinary_record.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->getJsonAuth(route('disciplinary_record.show', $item->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $item = $this->model::factory()->create();
        $data = $this->model::factory()->make()->toArray();
        $response = $this->putJsonAuth(route('disciplinary_record.update', $item->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $item = $this->model::factory()->create();
        $response = $this->deleteJsonAuth(route('disciplinary_record.destroy', $item->id));
        $response->assertStatus(204);
    }
}
