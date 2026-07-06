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

    public function store(array $data): mixed
    {
        $data = $this->clean($data);

        $result = $this->repository->store($data);

        if (is_array($result)) {
            return array_map(fn($m) => $m->fresh(), $result);
        }

        return $result->fresh();
    }
}
