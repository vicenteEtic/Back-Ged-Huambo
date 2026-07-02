<?php

namespace App\Services\RH\Recruitment;

use App\Repositories\RH\Recruitment\CandidateRepository;
use App\Services\AbstractService;

class CandidateService extends AbstractService
{
    public function __construct(CandidateRepository $repository)
    {
        parent::__construct($repository);
    }
}
