<?php

namespace App\Repositories\RH\Benefit;

use App\Models\RH\Benefit\MedicalAssistance;
use App\Repositories\AbstractRepository;

class MedicalAssistanceRepository extends AbstractRepository
{
    public function __construct(MedicalAssistance $model)
    {
        parent::__construct($model);
    }
}
