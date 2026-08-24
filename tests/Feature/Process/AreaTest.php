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

    public function test_can_create_without_department(): void
    {
        $data = Area::factory()->make()->toArray();

        $response = $this->postJsonAuth(route('area.store'), $data);
        $response->assertStatus(201);
    }

    public function test_area_ignores_department_id(): void
    {
        $data = Area::factory()->make(['department_id' => 999999])->toArray();

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

    public function test_department_can_be_associated_with_area(): void
    {
        $area = Area::factory()->create();
        $dept = Department::factory()->create();

        $response = $this->putJsonAuth("/api/rh/departments/{$dept->id}", [
            'area_id' => $area->id,
        ]);

        $response->assertStatus(200);
        $this->assertSame($area->id, $dept->fresh()->area_id);
    }

    public function test_one_area_can_have_multiple_departments(): void
    {
        $area = Area::factory()->create();

        Department::factory()->count(3)->create(['area_id' => $area->id]);

        $this->assertCount(3, $area->fresh()->departments);
    }

    public function test_by_department_returns_the_area_of_the_department(): void
    {
        $area = Area::factory()->create();
        $dept = Department::factory()->create(['area_id' => $area->id]);

        $response = $this->getJsonAuth(route('area.byDepartment', $dept->id));
        $response->assertStatus(200);
        $response->assertJsonPath('0.id', $area->id);
    }
}
