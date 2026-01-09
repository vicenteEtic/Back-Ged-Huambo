<?php
namespace App\Repositories\Transation;

use App\Models\Transation\Policies;
use App\Repositories\AbstractRepository;

class PoliciesRepository extends AbstractRepository
{
    public function __construct(Policies $model)
    {
        parent::__construct($model);
    }
}