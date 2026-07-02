<?php

namespace App\Services\RH\Leave;

use App\Repositories\RH\Leave\LeaveTypeRepository;
use App\Services\AbstractService;

class LeaveTypeService extends AbstractService
{
    public function __construct(LeaveTypeRepository $repository)
    {
        parent::__construct($repository);
    }
}
