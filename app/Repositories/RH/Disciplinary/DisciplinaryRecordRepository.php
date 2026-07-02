<?php

namespace App\Repositories\RH\Disciplinary;

use App\Models\RH\Disciplinary\DisciplinaryRecord;
use App\Repositories\AbstractRepository;

class DisciplinaryRecordRepository extends AbstractRepository
{
    public function __construct(DisciplinaryRecord $model) { parent::__construct($model); }
}
