<?php

namespace App\Services\Indicator;

use App\Repositories\Indicator\IndicatorTypeRepository;
use App\Services\AbstractService;

class IndicatorTypeService extends AbstractService
{
    public function __construct(IndicatorTypeRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getByDescription(?string $description = null)
    {
      return  $indicator = $this->repository->getByDescription($description);
    }
}
