<?php

namespace App\Services\RH\Payroll;

use App\Repositories\RH\Payroll\PayrollPeriodRepository;
use App\Services\AbstractService;

class PayrollPeriodService extends AbstractService
{
    public function __construct(PayrollPeriodRepository $repository)
    {
        parent::__construct($repository);
    }
}
