<?php

namespace App\Services\Process;

use App\Repositories\Process\ProcessCommentRepository;
use App\Services\AbstractService;

class ProcessCommentService extends AbstractService
{
    public function __construct(ProcessCommentRepository $repository)
    {
        parent::__construct($repository);
    }

    public function byProcess(int $processId)
    {
        return $this->repository->byProcess($processId);
    }
}
