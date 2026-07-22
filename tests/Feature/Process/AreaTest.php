<?php

namespace Tests\Feature\Process;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Area\Area;
use App\Models\RH\Department\Department;

class AreaTest extends RhTestCase
{
    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('area.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $dept = Department::factory()->create();
        $data = Area::factory()->make(['department_id' => $dept->id])->toArray();

        $response = $this->postJsonAuth(route('area.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $area = Area::factory()->create();
        $response = $this->getJsonAuth(route('area.show', $area->id));
        $response->assertStatus(200);
    }

    public function test_can_update(): void
    {
        $area = Area::factory()->create();
        $data = $area->toArray();
        $data['name'] = 'Área Atualizada';

        $response = $this->putJsonAuth(route('area.update', $area->id), $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $area = Area::factory()->create();
        $response = $this->deleteJsonAuth(route('area.destroy', $area->id));
        $response->assertStatus(204);
    }

    public function test_can_list_by_department(): void
    {
        $dept = Department::factory()->create();
        Area::factory()->count(3)->create(['department_id' => $dept->id]);

        $response = $this->getJsonAuth(route('area.byDepartment', $dept->id));
        $response->assertStatus(200);
    }
}
