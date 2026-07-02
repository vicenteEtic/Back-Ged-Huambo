<?php

namespace App\Repositories\RH\Performance;

use App\Models\RH\Performance\PerformanceCycle;
use App\Repositories\AbstractRepository;

class PerformanceCycleRepository extends AbstractRepository
{
    public function __construct(PerformanceCycle $model) { parent::__construct($model); }
}
