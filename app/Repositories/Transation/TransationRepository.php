<?php
namespace App\Repositories\Transation;

use App\Models\Transation\Transation;
use App\Repositories\AbstractRepository;

class TransationRepository extends AbstractRepository
{
    public function __construct(Transation $model)
    {
        parent::__construct($model);
    }
}