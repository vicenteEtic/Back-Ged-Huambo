<?php
namespace App\Repositories\Entities;

use App\Models\Entities\RiskAssessmentControl;
use App\Repositories\AbstractRepository;

class RiskAssessmentControlRepository extends AbstractRepository
{
    public function __construct(RiskAssessmentControl $model)
    {
        parent::__construct($model);
    }

    
}