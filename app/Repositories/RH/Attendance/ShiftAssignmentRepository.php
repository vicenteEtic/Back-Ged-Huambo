<?php

namespace App\Repositories\RH\Attendance;

use App\Models\RH\Attendance\ShiftAssignment;
use App\Repositories\AbstractRepository;

class ShiftAssignmentRepository extends AbstractRepository
{
    public function __construct(ShiftAssignment $model)
    {
        parent::__construct($model);
    }
}
