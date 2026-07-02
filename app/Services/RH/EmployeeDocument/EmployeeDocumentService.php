<?php

namespace App\Services\RH\EmployeeDocument;

use App\Repositories\RH\EmployeeDocument\EmployeeDocumentRepository;
use App\Services\AbstractService;

class EmployeeDocumentService extends AbstractService
{
    public function __construct(EmployeeDocumentRepository $repository)
    {
        parent::__construct($repository);
    }
}
