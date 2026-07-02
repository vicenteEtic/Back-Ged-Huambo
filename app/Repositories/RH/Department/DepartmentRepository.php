<?php

namespace App\Repositories\RH\Department;

use App\Models\RH\Department\Department;
use App\Repositories\AbstractRepository;

class DepartmentRepository extends AbstractRepository
{
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }
}
