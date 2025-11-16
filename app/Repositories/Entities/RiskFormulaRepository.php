<?php

namespace App\Repositories\Entities;

use App\Models\Entities\RiskFormula;
use App\Repositories\AbstractRepository;

class RiskFormulaRepository extends AbstractRepository
{
    public function __construct(RiskFormula $model)
    {
        parent::__construct($model);
    }

    public function findByEntityType($entityType)
    {
        return $this->model::where('entity_type', $entityType)->first();
    }
}
