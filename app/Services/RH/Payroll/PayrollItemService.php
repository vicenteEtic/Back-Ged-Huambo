<?php

namespace App\Services\RH\Payroll;

use App\Repositories\RH\Payroll\PayrollItemRepository;
use App\Services\AbstractService;

class PayrollItemService extends AbstractService
{
    public function __construct(PayrollItemRepository $repository)
    {
        parent::__construct($repository);
    }
}
