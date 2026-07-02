<?php

namespace App\Repositories\RH\Recruitment;

use App\Models\RH\Recruitment\Application;
use App\Repositories\AbstractRepository;

class ApplicationRepository extends AbstractRepository
{
    public function __construct(Application $model)
    {
        parent::__construct($model);
    }
}
