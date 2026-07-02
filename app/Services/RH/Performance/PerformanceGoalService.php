<?php

namespace App\Services\RH\Performance;

use App\Repositories\RH\Performance\PerformanceGoalRepository;
use App\Services\AbstractService;

class PerformanceGoalService extends AbstractService
{
    public function __construct(PerformanceGoalRepository $repository) { parent::__construct($repository); }
}
