<?php

namespace App\Repositories\RH\Leave;

use App\Models\RH\Leave\Holiday;
use App\Repositories\AbstractRepository;

class HolidayRepository extends AbstractRepository
{
    public function __construct(Holiday $model)
    {
        parent::__construct($model);
    }
}
