<?php

namespace App\Services\RH\Department;

use App\Repositories\RH\Department\DepartmentRepository;
use App\Services\AbstractService;

class DepartmentService extends AbstractService
{
    public function __construct(DepartmentRepository $repository)
    {
        parent::__construct($repository);
    }
}
