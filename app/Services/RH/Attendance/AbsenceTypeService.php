<?php

namespace App\Services\RH\Attendance;

use App\Repositories\RH\Attendance\AbsenceTypeRepository;
use App\Services\AbstractService;

class AbsenceTypeService extends AbstractService
{
    public function __construct(AbsenceTypeRepository $repository)
    {
        parent::__construct($repository);
    }
}
