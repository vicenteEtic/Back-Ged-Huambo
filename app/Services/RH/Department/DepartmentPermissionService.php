<?php

namespace App\Services\RH\Department;

use App\Repositories\RH\Department\DepartmentPermissionRepository;
use App\Services\AbstractService;

class DepartmentPermissionService extends AbstractService
{
    public function __construct(DepartmentPermissionRepository $repository)
    {
        parent::__construct($repository);
    }

    public function byDepartment(int $departmentId)
    {
        return $this->repository->byDepartment($departmentId);
    }
}
