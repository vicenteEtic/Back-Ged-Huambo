<?php

namespace App\Repositories\RH\Department;

use App\Models\RH\Department\DepartmentPermission;
use App\Repositories\AbstractRepository;

class DepartmentPermissionRepository extends AbstractRepository
{
    public function __construct(DepartmentPermission $model)
    {
        parent::__construct($model);
    }

    public function byDepartment(int $departmentId)
    {
        return $this->model->where('department_id', $departmentId)
            ->with(['permission', 'area', 'grantedBy'])
            ->get();
    }
}
