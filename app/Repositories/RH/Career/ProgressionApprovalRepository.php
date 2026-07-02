<?php

namespace App\Repositories\RH\Career;

use App\Models\RH\Career\ProgressionApproval;
use App\Repositories\AbstractRepository;

class ProgressionApprovalRepository extends AbstractRepository
{
    public function __construct(ProgressionApproval $model)
    {
        parent::__construct($model);
    }
}
