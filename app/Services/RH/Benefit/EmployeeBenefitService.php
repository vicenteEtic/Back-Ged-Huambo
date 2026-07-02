<?php

namespace App\Services\RH\Benefit;

use App\Repositories\RH\Benefit\EmployeeBenefitRepository;
use App\Services\AbstractService;

class EmployeeBenefitService extends AbstractService
{
    public function __construct(EmployeeBenefitRepository $repository) { parent::__construct($repository); }
}
