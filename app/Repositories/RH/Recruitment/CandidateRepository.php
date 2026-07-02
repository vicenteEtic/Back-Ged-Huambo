<?php

namespace App\Repositories\RH\Recruitment;

use App\Models\RH\Recruitment\Candidate;
use App\Repositories\AbstractRepository;

class CandidateRepository extends AbstractRepository
{
    public function __construct(Candidate $model)
    {
        parent::__construct($model);
    }
}
