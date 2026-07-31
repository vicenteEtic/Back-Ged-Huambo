<?php

namespace App\Services\RH\Area;

use App\Repositories\RH\Area\AreaRepository;
use App\Services\AbstractService;

class AreaService extends AbstractService
{
    public function __construct(AreaRepository $repository)
    {
        parent::__construct($repository);
    }
}
