<?php

namespace App\Repositories\RH\EmployeeDocument;

use App\Models\RH\EmployeeDocument\DocumentType;
use App\Repositories\AbstractRepository;

class DocumentTypeRepository extends AbstractRepository
{
    public function __construct(DocumentType $model)
    {
        parent::__construct($model);
    }
}
