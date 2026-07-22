<?php

namespace App\Services\Process;

use App\Repositories\Process\ProcessMovementRepository;
use App\Services\AbstractService;

class ProcessMovementService extends AbstractService
{
    public function __construct(ProcessMovementRepository $repository)
    {
        parent::__construct($repository);
    }

    public function byProcess(int $processId)
    {
        return $this->repository->byProcess($processId);
    }
}
