<?php

namespace App\Services\RH\Attendance;

use App\Repositories\RH\Attendance\ShiftRepository;
use App\Services\AbstractService;

class ShiftService extends AbstractService
{
    public function __construct(ShiftRepository $repository)
    {
        parent::__construct($repository);
    }
}
