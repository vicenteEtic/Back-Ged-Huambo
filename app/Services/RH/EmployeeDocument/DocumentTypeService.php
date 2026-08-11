<?php

namespace App\Services\RH\EmployeeDocument;

use App\Repositories\RH\EmployeeDocument\DocumentTypeRepository;
use App\Services\AbstractService;

class DocumentTypeService extends AbstractService
{
    public function __construct(DocumentTypeRepository $repository)
    {
        parent::__construct($repository);
    }
}
