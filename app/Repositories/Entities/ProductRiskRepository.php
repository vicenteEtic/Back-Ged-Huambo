<?php
namespace App\Repositories\Entities;

use App\Models\Entities\ProductRisk;
use App\Repositories\AbstractRepository;

class ProductRiskRepository extends AbstractRepository
{
    public function __construct(ProductRisk $model)
    {
        parent::__construct($model);
    }

      public function showProduct($data){
    return     $this->model->where('risk_assessment_id',$data
            )->get();
    }
}