<?php
namespace App\Repositories\Transation\CustomerProfiles;

use App\Models\Transation\CustomerProfiles\CustomerProfiles;
use App\Repositories\AbstractRepository;

class CustomerProfilesRepository extends AbstractRepository
{
    public function __construct(CustomerProfiles $model)
    {
        parent::__construct($model);
    }
}