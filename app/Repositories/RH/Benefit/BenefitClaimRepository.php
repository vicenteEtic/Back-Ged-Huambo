<?php

namespace App\Repositories\RH\Benefit;

use App\Models\RH\Benefit\BenefitClaim;
use App\Repositories\AbstractRepository;

class BenefitClaimRepository extends AbstractRepository
{
    public function __construct(BenefitClaim $model)
    {
        parent::__construct($model);
    }
}
