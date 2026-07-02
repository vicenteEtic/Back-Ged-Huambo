<?php

namespace App\Services\RH\Recruitment;

use App\Repositories\RH\Recruitment\ApplicationRepository;
use App\Services\AbstractService;

class ApplicationService extends AbstractService
{
    public function __construct(ApplicationRepository $repository)
    {
        parent::__construct($repository);
    }
}
