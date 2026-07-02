<?php

namespace App\Repositories\RH\EmployeeDocument;

use App\Models\RH\EmployeeDocument\EmployeeDocument;
use App\Repositories\AbstractRepository;

class EmployeeDocumentRepository extends AbstractRepository
{
    public function __construct(EmployeeDocument $model)
    {
        parent::__construct($model);
    }
}
