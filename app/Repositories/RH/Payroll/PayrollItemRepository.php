<?php

namespace App\Repositories\RH\Payroll;

use App\Models\RH\Payroll\PayrollItem;
use App\Repositories\AbstractRepository;

class PayrollItemRepository extends AbstractRepository
{
    public function __construct(PayrollItem $model)
    {
        parent::__construct($model);
    }
}
