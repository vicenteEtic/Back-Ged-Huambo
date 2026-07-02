<?php

namespace App\Repositories\RH\Career;

use App\Models\RH\Career\RetirementProcess;
use App\Repositories\AbstractRepository;

class RetirementProcessRepository extends AbstractRepository
{
    public function __construct(RetirementProcess $model)
    {
        parent::__construct($model);
    }
}
