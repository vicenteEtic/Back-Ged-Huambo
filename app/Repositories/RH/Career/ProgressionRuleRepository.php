<?php

namespace App\Repositories\RH\Career;

use App\Models\RH\Career\ProgressionRule;
use App\Repositories\AbstractRepository;

class ProgressionRuleRepository extends AbstractRepository
{
    public function __construct(ProgressionRule $model)
    {
        parent::__construct($model);
    }
}
