<?php

namespace App\Services\RH\Employee;

use App\Repositories\RH\Employee\EmployeeRepository;
use App\Services\AbstractService;

class EmployeeService extends AbstractService
{
    public function __construct(EmployeeRepository $repository)
    {
        parent::__construct($repository);
    }
}
