<?php

namespace Tests\Feature\Process;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Department\DepartmentPermission;
use App\Models\RH\Department\Department;
use App\Models\RH\Area\Area;
use App\Models\Permission\Permission;

class DepartmentPermissionTest extends RhTestCase
{
    public function test_can_list(): void
    {
        $response = $this->getJsonAuth(route('department-permission.index'));
        $response->assertStatus(200);
    }

    public function test_can_create(): void
    {
        $dept = Department::factory()->create();
        $permission = Permission::factory()->create();

        $data = DepartmentPermission::factory()->make([
            'department_id' => $dept->id,
            'permission_id' => $permission->id,
            'granted_by' => $this->user->id,
        ])->toArray();

        $response = $this->postJsonAuth(route('department-permission.store'), $data);
        $response->assertStatus(201);
    }

    public function test_can_show(): void
    {
        $dp = DepartmentPermission::factory()->create();
        $response = $this->getJsonAuth(route('department-permission.show', $dp->id));
        $response->assertStatus(200);
    }

    public function test_can_destroy(): void
    {
        $dp = DepartmentPermission::factory()->create();
        $response = $this->deleteJsonAuth(route('department-permission.destroy', $dp->id));
        $response->assertStatus(204);
    }

    public function test_can_list_by_department(): void
    {
        $dept = Department::factory()->create();
        DepartmentPermission::factory()->count(3)->create(['department_id' => $dept->id]);

        $response = $this->getJsonAuth(route('department-permission.byDepartment', $dept->id));
        $response->assertStatus(200);
    }
}
