<?php

namespace App\Services\RH\Recruitment;

use App\Repositories\RH\Recruitment\JobOpeningRepository;
use App\Services\AbstractService;

class JobOpeningService extends AbstractService
{
    public function __construct(JobOpeningRepository $repository)
    {
        parent::__construct($repository);
    }
}
