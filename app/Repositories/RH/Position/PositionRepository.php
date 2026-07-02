<?php

namespace App\Repositories\RH\Position;

use App\Models\RH\Position\Position;
use App\Repositories\AbstractRepository;

class PositionRepository extends AbstractRepository
{
    public function __construct(Position $model)
    {
        parent::__construct($model);
    }
}
