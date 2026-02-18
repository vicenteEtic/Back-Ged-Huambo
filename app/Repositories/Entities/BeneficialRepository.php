<?php
namespace App\Repositories\Entities;

use App\Models\Entities\Beneficial;
use App\Repositories\AbstractRepository;

class BeneficialRepository extends AbstractRepository
{
    public function __construct(Beneficial $model)
    {
        parent::__construct($model);
    }
}