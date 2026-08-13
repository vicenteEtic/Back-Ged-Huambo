<?php

namespace App\Repositories\RH\Attendance;

use App\Models\RH\Attendance\AbsenceType;
use App\Repositories\AbstractRepository;

class AbsenceTypeRepository extends AbstractRepository
{
    public function __construct(AbsenceType $model)
    {
        parent::__construct($model);
    }
}
