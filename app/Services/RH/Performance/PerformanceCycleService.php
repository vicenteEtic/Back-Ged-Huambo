<?php

namespace App\Services\RH\Performance;

use App\Repositories\RH\Performance\PerformanceCycleRepository;
use App\Services\AbstractService;

class PerformanceCycleService extends AbstractService
{
    public function __construct(PerformanceCycleRepository $repository) { parent::__construct($repository); }
}
