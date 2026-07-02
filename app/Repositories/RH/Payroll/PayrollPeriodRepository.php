<?php

namespace App\Repositories\RH\Payroll;

use App\Models\RH\Payroll\PayrollPeriod;
use App\Repositories\AbstractRepository;

class PayrollPeriodRepository extends AbstractRepository
{
    public function __construct(PayrollPeriod $model)
    {
        parent::__construct($model);
    }
}
