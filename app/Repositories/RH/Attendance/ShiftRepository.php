<?php

namespace App\Repositories\RH\Attendance;

use App\Models\RH\Attendance\Shift;
use App\Repositories\AbstractRepository;

class ShiftRepository extends AbstractRepository
{
    public function __construct(Shift $model)
    {
        parent::__construct($model);
    }
}
