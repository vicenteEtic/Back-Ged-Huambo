<?php

namespace App\Repositories\RH\FunctionalHistory;

use App\Models\RH\FunctionalHistory\FunctionalHistory;
use App\Repositories\AbstractRepository;

class FunctionalHistoryRepository extends AbstractRepository
{
    public function __construct(FunctionalHistory $model)
    {
        parent::__construct($model);
    }
}
