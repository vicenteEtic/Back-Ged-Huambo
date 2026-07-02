<?php

namespace App\Repositories\RH\Recruitment;

use App\Models\RH\Recruitment\JobOpening;
use App\Repositories\AbstractRepository;

class JobOpeningRepository extends AbstractRepository
{
    public function __construct(JobOpening $model)
    {
        parent::__construct($model);
    }
}
