<?php

namespace App\Services\RH\Disciplinary;

use App\Repositories\RH\Disciplinary\DisciplinaryTypeRepository;
use App\Services\AbstractService;

class DisciplinaryTypeService extends AbstractService
{
    public function __construct(DisciplinaryTypeRepository $repository) { parent::__construct($repository); }
}
