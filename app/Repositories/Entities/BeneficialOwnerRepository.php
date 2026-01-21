<?php
namespace App\Repositories\Entities;

use App\Models\Entities\BeneficialOwner;
use App\Repositories\AbstractRepository;

class BeneficialOwnerRepository extends AbstractRepository
{
    public function __construct(BeneficialOwner $model)
    {
        parent::__construct($model);
    }

    public function showBeneficialOwner($data)
    {
      return     $this->model->where(
        'risk_assessment_id',
        $data
      )->get();
    }
}