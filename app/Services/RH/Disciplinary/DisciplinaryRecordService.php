<?php

namespace App\Services\RH\Disciplinary;

use App\Repositories\RH\Disciplinary\DisciplinaryRecordRepository;
use App\Services\AbstractService;

class DisciplinaryRecordService extends AbstractService
{
    public function __construct(DisciplinaryRecordRepository $repository) { parent::__construct($repository); }
}
