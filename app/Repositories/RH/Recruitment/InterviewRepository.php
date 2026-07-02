<?php

namespace App\Repositories\RH\Recruitment;

use App\Models\RH\Recruitment\Interview;
use App\Repositories\AbstractRepository;

class InterviewRepository extends AbstractRepository
{
    public function __construct(Interview $model)
    {
        parent::__construct($model);
    }
}
