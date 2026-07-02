<?php

namespace App\Services\RH\Performance;

use App\Repositories\RH\Performance\EvaluationCriterionRepository;
use App\Services\AbstractService;

class EvaluationCriterionService extends AbstractService
{
    public function __construct(EvaluationCriterionRepository $repository)
    {
        parent::__construct($repository);
    }
}
