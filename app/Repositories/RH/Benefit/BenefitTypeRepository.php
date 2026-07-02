<?php

namespace App\Repositories\RH\Benefit;

use App\Models\RH\Benefit\BenefitType;
use App\Repositories\AbstractRepository;

class BenefitTypeRepository extends AbstractRepository
{
    public function __construct(BenefitType $model) { parent::__construct($model); }
}
