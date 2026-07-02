<?php

namespace App\Repositories\RH\Performance;

use App\Models\RH\Performance\EvaluationScore;
use App\Repositories\AbstractRepository;

class EvaluationScoreRepository extends AbstractRepository
{
    public function __construct(EvaluationScore $model)
    {
        parent::__construct($model);
    }
}
