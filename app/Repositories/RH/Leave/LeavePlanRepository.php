<?php

namespace App\Repositories\RH\Leave;

use App\Models\RH\Leave\LeavePlan;
use App\Repositories\AbstractRepository;

class LeavePlanRepository extends AbstractRepository
{
    public function __construct(LeavePlan $model)
    {
        parent::__construct($model);
    }
}
