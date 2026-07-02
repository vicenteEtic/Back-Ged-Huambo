<?php

namespace App\Repositories\RH\Leave;

use App\Models\RH\Leave\LeaveType;
use App\Repositories\AbstractRepository;

class LeaveTypeRepository extends AbstractRepository
{
    public function __construct(LeaveType $model)
    {
        parent::__construct($model);
    }
}
