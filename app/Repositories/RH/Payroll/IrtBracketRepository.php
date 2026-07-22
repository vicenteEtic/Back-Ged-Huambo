<?php

namespace App\Repositories\RH\Payroll;

use App\Models\RH\Payroll\IrtBracket;
use App\Repositories\AbstractRepository;

class IrtBracketRepository extends AbstractRepository
{
    public function __construct(IrtBracket $model)
    {
        parent::__construct($model);
    }
}
