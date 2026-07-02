<?php

namespace App\Repositories\RH\Performance;

use App\Models\RH\Performance\EvaluationCriterion;
use App\Repositories\AbstractRepository;

class EvaluationCriterionRepository extends AbstractRepository
{
    public function __construct(EvaluationCriterion $model)
    {
        parent::__construct($model);
    }
}
