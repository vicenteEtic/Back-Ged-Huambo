<?php

namespace App\Services\RH\Recruitment;

use App\Repositories\RH\Recruitment\InterviewRepository;
use App\Services\AbstractService;

class InterviewService extends AbstractService
{
    public function __construct(InterviewRepository $repository)
    {
        parent::__construct($repository);
    }
}
