<?php

namespace App\Repositories\RH\Area;

use App\Models\RH\Area\Area;
use App\Repositories\AbstractRepository;

class AreaRepository extends AbstractRepository
{
    public function __construct(Area $model)
    {
        parent::__construct($model);
    }
}
