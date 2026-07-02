<?php

namespace App\Repositories\RH\Benefit;

use App\Models\RH\Benefit\EmployeeBenefit;
use App\Repositories\AbstractRepository;

class EmployeeBenefitRepository extends AbstractRepository
{
    public function __construct(EmployeeBenefit $model) { parent::__construct($model); }
}
