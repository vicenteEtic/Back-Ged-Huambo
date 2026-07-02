<?php

namespace App\Services\RH\Benefit;

use App\Repositories\RH\Benefit\BenefitClaimRepository;
use App\Services\AbstractService;

class BenefitClaimService extends AbstractService
{
    public function __construct(BenefitClaimRepository $repository)
    {
        parent::__construct($repository);
    }
}
