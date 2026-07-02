<?php

namespace App\Repositories\RH\Employee;

use App\Models\RH\Employee\Employee;
use App\Repositories\AbstractRepository;

class EmployeeRepository extends AbstractRepository
{
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }
}
