<?php

namespace App\Repositories\RH\Leave;

use App\Models\RH\Leave\LeaveRequest;
use App\Repositories\AbstractRepository;

class LeaveRequestRepository extends AbstractRepository
{
    public function __construct(LeaveRequest $model)
    {
        parent::__construct($model);
    }
}
