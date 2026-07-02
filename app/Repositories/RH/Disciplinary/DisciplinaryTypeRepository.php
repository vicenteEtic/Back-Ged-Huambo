<?php

namespace App\Repositories\RH\Disciplinary;

use App\Models\RH\Disciplinary\DisciplinaryType;
use App\Repositories\AbstractRepository;

class DisciplinaryTypeRepository extends AbstractRepository
{
    public function __construct(DisciplinaryType $model) { parent::__construct($model); }
}
