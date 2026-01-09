<?php
namespace App\Repositories\Transation;

use App\Models\Transation\transaionControl;
use App\Repositories\AbstractRepository;

class transaionControlRepository extends AbstractRepository
{
    public function __construct(transaionControl $model)
    {
        parent::__construct($model);
    }
}