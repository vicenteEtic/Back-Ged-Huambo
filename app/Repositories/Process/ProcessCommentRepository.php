<?php

namespace App\Repositories\Process;

use App\Models\Process\ProcessComment;
use App\Repositories\AbstractRepository;

class ProcessCommentRepository extends AbstractRepository
{
    public function __construct(ProcessComment $model)
    {
        parent::__construct($model);
    }

    public function byProcess(int $processId)
    {
        return $this->model->where('process_id', $processId)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }
}
