<?php

namespace App\Services\RH\Attendance;

use App\Repositories\RH\Attendance\ShiftAssignmentRepository;
use App\Services\AbstractService;

class ShiftAssignmentService extends AbstractService
{
    public function __construct(ShiftAssignmentRepository $repository)
    {
        parent::__construct($repository);
    }
}
