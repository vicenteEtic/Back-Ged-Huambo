<?php

namespace App\Repositories\RH\Performance;

use App\Models\RH\Performance\PerformanceGoal;
use App\Repositories\AbstractRepository;

class PerformanceGoalRepository extends AbstractRepository
{
    public function __construct(PerformanceGoal $model) { parent::__construct($model); }
}
