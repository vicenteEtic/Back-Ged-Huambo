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

    public function store(array $data)
    {
        $existing = $this->model->withTrashed()
            ->where('payroll_period_id', $data['payroll_period_id'])
            ->where('employee_id', $data['employee_id'])
            ->first();

        if ($existing && $existing->trashed()) {
            $existing->forceDelete();
        }

        return parent::store($data);
    }
}
