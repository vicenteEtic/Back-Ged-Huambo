<?php

namespace Database\Factories\RH\Department;

use App\Models\RH\Department\DepartmentPermission;
use App\Models\RH\Department\Department;
use App\Models\RH\Area\Area;
use App\Models\Permission\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentPermissionFactory extends Factory
{
    protected $model = DepartmentPermission::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'area_id' => null,
            'permission_id' => Permission::factory(),
            'granted_by' => User::factory(),
            'is_active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
