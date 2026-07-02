<?php

namespace App\Repositories\RH\Leave;

use App\Models\RH\Leave\LeaveApproval;
use App\Repositories\AbstractRepository;

class LeaveApprovalRepository extends AbstractRepository
{
    public function __construct(LeaveApproval $model)
    {
        parent::__construct($model);
    }
}
