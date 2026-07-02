<?php

namespace App\Services\RH\Position;

use App\Repositories\RH\Position\PositionRepository;
use App\Services\AbstractService;

class PositionService extends AbstractService
{
    public function __construct(PositionRepository $repository)
    {
        parent::__construct($repository);
    }
}
