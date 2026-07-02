<?php

namespace App\Repositories\RH\Performance;

use App\Models\RH\Performance\PerformanceEvaluation;
use App\Repositories\AbstractRepository;

class PerformanceEvaluationRepository extends AbstractRepository
{
    public function __construct(PerformanceEvaluation $model) { parent::__construct($model); }
}
