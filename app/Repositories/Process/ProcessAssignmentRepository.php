<?php

namespace App\Repositories\Process;

use App\Models\Process\ProcessAssignment;
use App\Repositories\AbstractRepository;

class ProcessAssignmentRepository extends AbstractRepository
{
    public function __construct(ProcessAssignment $model)
    {
        parent::__construct($model);
    }
}
