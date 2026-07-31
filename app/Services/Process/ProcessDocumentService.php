<?php

namespace App\Services\Process;

use App\Repositories\Process\ProcessDocumentRepository;
use App\Services\AbstractService;

class ProcessDocumentService extends AbstractService
{
    public function __construct(ProcessDocumentRepository $repository)
    {
        parent::__construct($repository);
    }

    public function store(array $data): mixed
    {
        $data = $this->clean($data);

        $result = $this->repository->store($data);

        if (is_array($result)) {
            return array_map(fn ($m) => $m->fresh(), $result);
        }

        return $result->fresh();
    }

    public function byProcess(int $processId)
    {
        return $this->repository->byProcess($processId);
    }
}
