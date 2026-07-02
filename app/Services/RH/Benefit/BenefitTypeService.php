<?php

namespace App\Services\RH\Benefit;

use App\Repositories\RH\Benefit\BenefitTypeRepository;
use App\Services\AbstractService;

class BenefitTypeService extends AbstractService
{
    public function __construct(BenefitTypeRepository $repository) { parent::__construct($repository); }
}
