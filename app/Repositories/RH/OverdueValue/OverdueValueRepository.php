<?php

namespace App\Repositories\RH\OverdueValue;

use App\Models\RH\OverdueValue\OverdueValue;
use App\Repositories\AbstractRepository;

class OverdueValueRepository extends AbstractRepository
{
    public function __construct(OverdueValue $model)
    {
        parent::__construct($model);
    }
}
