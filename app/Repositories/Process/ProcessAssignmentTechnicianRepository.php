<?php

namespace App\Repositories\Process;

use App\Models\Process\ProcessAssignmentTechnician;
use App\Repositories\AbstractRepository;

class ProcessAssignmentTechnicianRepository extends AbstractRepository
{
    public function __construct(ProcessAssignmentTechnician $model)
    {
        parent::__construct($model);
    }
}
