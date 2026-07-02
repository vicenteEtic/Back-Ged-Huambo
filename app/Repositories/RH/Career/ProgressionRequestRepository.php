<?php

namespace App\Repositories\RH\Career;

use App\Models\RH\Career\ProgressionRequest;
use App\Repositories\AbstractRepository;

class ProgressionRequestRepository extends AbstractRepository
{
    public function __construct(ProgressionRequest $model)
    {
        parent::__construct($model);
    }
}
