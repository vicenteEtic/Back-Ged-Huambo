<?php
namespace App\Repositories\KYT;

use App\Models\KYT\kytrules;
use App\Repositories\AbstractRepository;

class kytrulesRepository extends AbstractRepository
{
    public function __construct(kytrules $model)
    {
        parent::__construct($model);
    }
    public function FindRole($role){
     return   $this->model::where('code',$role)->first();
    }
}